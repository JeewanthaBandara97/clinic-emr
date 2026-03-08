<?php
/**
 * Clinic Session Class
 */

require_once __DIR__ . '/Database.php';

class ClinicSession {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function generateSessionCode(): string {
        $date = date('Ymd');
        $sql = "SELECT COUNT(*) as count FROM clinic_sessions WHERE session_date = CURDATE()";
        $result = $this->db->fetchOne($sql);
        $count = ($result['count'] ?? 0) + 1;
        return 'SES' . $date . str_pad($count, 3, '0', STR_PAD_LEFT);
    }
    
    public function create(array $data): int {
        $sql = "INSERT INTO clinic_sessions (
                    session_code, doctor_id, session_date, start_time, 
                    max_patients, notes, status, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, 'Scheduled', ?)";
        
        return $this->db->insert($sql, [
            $data['session_code'],
            $data['doctor_id'],
            $data['session_date'],
            $data['start_time'],
            $data['max_patients'] ?? 50,
            $data['notes'] ?? null,
            $data['created_by']
        ]);
    }
    
    public function getById(int $sessionId): ?array {
        $sql = "SELECT cs.*, u.full_name as doctor_name 
                FROM clinic_sessions cs
                JOIN users u ON cs.doctor_id = u.user_id
                WHERE cs.session_id = ?";
        return $this->db->fetchOne($sql, [$sessionId]);
    }
    
    public function getTodaySessions(): array {
        $sql = "SELECT cs.*, 
                    u.full_name as doctor_name,
                    (SELECT COUNT(*) FROM session_patients sp WHERE sp.session_id = cs.session_id) as total_patients,
                    (SELECT COUNT(*) FROM session_patients sp WHERE sp.session_id = cs.session_id AND sp.status = 'Waiting') as waiting_count,
                    (SELECT COUNT(*) FROM session_patients sp WHERE sp.session_id = cs.session_id AND sp.status = 'Completed') as completed_count
                FROM clinic_sessions cs
                JOIN users u ON cs.doctor_id = u.user_id
                WHERE cs.session_date = CURDATE()
                ORDER BY cs.start_time ASC";
        return $this->db->fetchAll($sql);
    }
    
    public function getActiveSessions(): array {
        $sql = "SELECT cs.*, u.full_name as doctor_name
                FROM clinic_sessions cs
                JOIN users u ON cs.doctor_id = u.user_id
                WHERE cs.session_date = CURDATE() 
                AND cs.status IN ('Scheduled', 'Active')
                ORDER BY cs.start_time ASC";
        return $this->db->fetchAll($sql);
    }
    
    public function getDoctorTodaySessions(int $doctorId): array {
        $sql = "SELECT cs.*,
                    (SELECT COUNT(*) FROM session_patients sp WHERE sp.session_id = cs.session_id) as total_patients,
                    (SELECT COUNT(*) FROM session_patients sp WHERE sp.session_id = cs.session_id AND sp.status = 'Waiting') as waiting_count
                FROM clinic_sessions cs
                WHERE cs.doctor_id = ? AND cs.session_date = CURDATE()
                ORDER BY cs.start_time ASC";
        return $this->db->fetchAll($sql, [$doctorId]);
    }
    
    public function addPatientToQueue(int $sessionId, int $patientId): int {
        $sql = "SELECT COALESCE(MAX(queue_number), 0) + 1 as next_queue 
                FROM session_patients WHERE session_id = ?";
        $result = $this->db->fetchOne($sql, [$sessionId]);
        $queueNumber = $result['next_queue'];
        
        $sql = "INSERT INTO session_patients (session_id, patient_id, queue_number, status)
                VALUES (?, ?, ?, 'Waiting')";
        $this->db->insert($sql, [$sessionId, $patientId, $queueNumber]);
        
        return $queueNumber;
    }
    
    public function isPatientInSession(int $sessionId, int $patientId): bool {
        $sql = "SELECT id FROM session_patients 
                WHERE session_id = ? AND patient_id = ?";
        return $this->db->fetchOne($sql, [$sessionId, $patientId]) !== null;
    }
    
    public function getSessionQueue(int $sessionId): array {
        $sql = "SELECT sp.*, 
                    p.patient_code, p.first_name, p.last_name, p.phone, p.gender,
                    TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) as age,
                    CONCAT(p.first_name, ' ', p.last_name) as patient_name
                FROM session_patients sp
                JOIN patients p ON sp.patient_id = p.patient_id
                WHERE sp.session_id = ?
                ORDER BY sp.queue_number ASC";
        return $this->db->fetchAll($sql, [$sessionId]);
    }
    
    public function getDoctorQueue(int $doctorId): array {
        $sql = "SELECT sp.*, 
                    p.patient_code, p.first_name, p.last_name, p.phone, p.gender,
                    TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) as age,
                    CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                    cs.session_code, cs.start_time
                FROM session_patients sp
                JOIN patients p ON sp.patient_id = p.patient_id
                JOIN clinic_sessions cs ON sp.session_id = cs.session_id
                WHERE cs.doctor_id = ? AND cs.session_date = CURDATE()
                ORDER BY sp.status = 'In Progress' DESC, sp.queue_number ASC";
        return $this->db->fetchAll($sql, [$doctorId]);
    }
    
    public function updatePatientStatus(int $sessionId, int $patientId, string $status): bool {
        $sql = "UPDATE session_patients SET status = ?, updated_at = NOW()";
        $params = [$status];
        
        if ($status === 'In Progress') {
            $sql .= ", start_time = NOW()";
        } elseif ($status === 'Completed') {
            $sql .= ", end_time = NOW()";
        }
        
        $sql .= " WHERE session_id = ? AND patient_id = ?";
        $params[] = $sessionId;
        $params[] = $patientId;
        
        return $this->db->update($sql, $params) > 0;
    }
    
    public function getTodayStats(): array {
        $sql = "SELECT 
                    (SELECT COUNT(*) FROM clinic_sessions WHERE session_date = CURDATE()) as total_sessions,
                    (SELECT COUNT(*) FROM session_patients sp 
                     JOIN clinic_sessions cs ON sp.session_id = cs.session_id 
                     WHERE cs.session_date = CURDATE()) as total_patients,
                    (SELECT COUNT(*) FROM session_patients sp 
                     JOIN clinic_sessions cs ON sp.session_id = cs.session_id 
                     WHERE cs.session_date = CURDATE() AND sp.status = 'Waiting') as waiting_patients,
                    (SELECT COUNT(*) FROM session_patients sp 
                     JOIN clinic_sessions cs ON sp.session_id = cs.session_id 
                     WHERE cs.session_date = CURDATE() AND sp.status = 'Completed') as completed_patients,
                    (SELECT COUNT(*) FROM patients WHERE DATE(registration_date) = CURDATE()) as new_patients";
        
        $result = $this->db->fetchOne($sql);
        return $result ?: [
            'total_sessions' => 0,
            'total_patients' => 0,
            'waiting_patients' => 0,
            'completed_patients' => 0,
            'new_patients' => 0
        ];
    }
 
    
    // ... (keep all existing methods) ...
    
    /**
     * Get all sessions with optional filters
     */
    public function getAllSessions(?string $date = null, ?string $doctorId = null, ?string $status = null): array {
        $sql = "SELECT cs.*, 
                    u.full_name as doctor_name,
                    (SELECT COUNT(*) FROM session_patients sp WHERE sp.session_id = cs.session_id) as total_patients,
                    (SELECT COUNT(*) FROM session_patients sp WHERE sp.session_id = cs.session_id AND sp.status = 'Waiting') as waiting_count,
                    (SELECT COUNT(*) FROM session_patients sp WHERE sp.session_id = cs.session_id AND sp.status = 'Completed') as completed_count
                FROM clinic_sessions cs
                JOIN users u ON cs.doctor_id = u.user_id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($date)) {
            $sql .= " AND cs.session_date >= ?";
            $params[] = $date;
        }
        
        if (!empty($doctorId)) {
            $sql .= " AND cs.doctor_id = ?";
            $params[] = (int)$doctorId;
        }
        
        if (!empty($status)) {
            $sql .= " AND cs.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY cs.session_date DESC, cs.start_time DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Delete a session (only if no patients)
     */
    public function deleteSession(int $sessionId): array {
        // First check if session has any patients
        $sql = "SELECT COUNT(*) as count FROM session_patients WHERE session_id = ?";
        $result = $this->db->fetchOne($sql, [$sessionId]);
        
        if ($result['count'] > 0) {
            return [
                'success' => false,
                'message' => 'Cannot delete session - it has ' . $result['count'] . ' patient(s) in queue. Remove patients first.'
            ];
        }
        
        // Check if session exists
        $session = $this->getById($sessionId);
        if (!$session) {
            return [
                'success' => false,
                'message' => 'Session not found.'
            ];
        }
        
        // Delete the session
        try {
            $sql = "DELETE FROM clinic_sessions WHERE session_id = ?";
            $deleted = $this->db->delete($sql, [$sessionId]);
            
            if ($deleted > 0) {
                return [
                    'success' => true,
                    'message' => 'Session deleted successfully.'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to delete session.'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error deleting session: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Remove patient from queue
     */
    public function removePatientFromQueue(int $sessionId, int $patientId): bool {
        try {
            $sql = "DELETE FROM session_patients WHERE session_id = ? AND patient_id = ?";
            $deleted = $this->db->delete($sql, [$sessionId, $patientId]);
            return $deleted > 0;
        } catch (Exception $e) {
            error_log("Error removing patient from queue: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update session status
     */
    public function updateStatus(int $sessionId, string $status): bool {
        try {
            $sql = "UPDATE clinic_sessions SET status = ?, updated_at = NOW() WHERE session_id = ?";
            $updated = $this->db->update($sql, [$status, $sessionId]);
            return $updated > 0;
        } catch (Exception $e) {
            error_log("Error updating session status: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get session statistics
     */
    public function getSessionStats(int $sessionId): array {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'Waiting' THEN 1 ELSE 0 END) as waiting,
                    SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'No Show' THEN 1 ELSE 0 END) as no_show,
                    SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled
                FROM session_patients 
                WHERE session_id = ?";
        
        $result = $this->db->fetchOne($sql, [$sessionId]);
        
        return $result ?: [
            'total' => 0,
            'waiting' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'no_show' => 0,
            'cancelled' => 0
        ];
    }
    
    /**
     * Bulk delete empty sessions
     */
    public function deleteEmptySessions(?string $beforeDate = null): int {
        $sql = "DELETE FROM clinic_sessions 
                WHERE session_id NOT IN (
                    SELECT DISTINCT session_id FROM session_patients
                )";
        
        $params = [];
        
        if ($beforeDate) {
            $sql .= " AND session_date < ?";
            $params[] = $beforeDate;
        }
        
        return $this->db->delete($sql, $params);
    }

    /**
     * Move patient up or down in queue
     */
    public function movePatientInQueue(int $sessionId, int $patientId, string $direction): bool {
        try {
            // Get current patient's queue info
            $sql = "SELECT id, queue_number FROM session_patients 
                    WHERE session_id = ? AND patient_id = ? AND status = 'Waiting'";
            $current = $this->db->fetchOne($sql, [$sessionId, $patientId]);
            
            if (!$current) {
                return false;
            }
            
            $currentQueueNum = $current['queue_number'];
            
            // Find adjacent patient to swap with
            if ($direction === 'up') {
                $sql = "SELECT id, patient_id, queue_number FROM session_patients 
                        WHERE session_id = ? AND queue_number < ? AND status = 'Waiting'
                        ORDER BY queue_number DESC LIMIT 1";
            } else {
                $sql = "SELECT id, patient_id, queue_number FROM session_patients 
                        WHERE session_id = ? AND queue_number > ? AND status = 'Waiting'
                        ORDER BY queue_number ASC LIMIT 1";
            }
            
            $adjacent = $this->db->fetchOne($sql, [$sessionId, $currentQueueNum]);
            
            if (!$adjacent) {
                return false; // No adjacent patient to swap with
            }
            
            // Swap queue numbers
            $this->db->beginTransaction();
            
            // Update current patient
            $sql = "UPDATE session_patients SET queue_number = ? WHERE id = ?";
            $this->db->update($sql, [$adjacent['queue_number'], $current['id']]);
            
            // Update adjacent patient
            $sql = "UPDATE session_patients SET queue_number = ? WHERE id = ?";
            $this->db->update($sql, [$currentQueueNum, $adjacent['id']]);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Error moving patient in queue: " . $e->getMessage());
            return false;
        }
    }


}
 
 