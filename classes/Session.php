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
}