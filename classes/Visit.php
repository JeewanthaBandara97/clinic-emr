<?php
/**
 * Visit Class
 * Handles patient visits, vital signs, and related data
 */

require_once __DIR__ . '/Database.php';

class Visit {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Generate unique visit code
     */
    public function generateVisitCode(): string {
        $date = date('Ymd');
        $sql = "SELECT COUNT(*) as count FROM visits WHERE visit_date = CURDATE()";
        $result = $this->db->fetchOne($sql);
        $count = ($result['count'] ?? 0) + 1;
        return 'VIS' . $date . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Create new visit
     */
    public function create(array $data): int {
        $sql = "INSERT INTO visits (
                    visit_code, patient_id, doctor_id, session_id,
                    visit_date, visit_time, symptoms, diagnosis, notes,
                    follow_up_date, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        return $this->db->insert($sql, [
            $data['visit_code'],
            $data['patient_id'],
            $data['doctor_id'],
            $data['session_id'] ?? null,
            $data['visit_date'],
            $data['visit_time'],
            $data['symptoms'] ?? null,
            $data['diagnosis'] ?? null,
            $data['notes'] ?? null,
            $data['follow_up_date'] ?? null,
            $data['status'] ?? 'In Progress'
        ]);
    }
    
    /**
     * Update visit
     */
    public function update(int $visitId, array $data): bool {
        $sql = "UPDATE visits SET
                    symptoms = ?, diagnosis = ?, notes = ?,
                    follow_up_date = ?, status = ?, updated_at = NOW()
                WHERE visit_id = ?";
        
        return $this->db->update($sql, [
            $data['symptoms'] ?? null,
            $data['diagnosis'] ?? null,
            $data['notes'] ?? null,
            $data['follow_up_date'] ?? null,
            $data['status'] ?? 'Completed',
            $visitId
        ]) >= 0;
    }
    
    /**
     * Get visit by ID
     */
    public function getById(int $visitId): ?array {
        $sql = "SELECT v.*, 
                    p.patient_code, p.first_name, p.last_name,
                    CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                    TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) as patient_age,
                    p.gender as patient_gender, p.blood_group, p.allergies, p.chronic_diseases,
                    u.full_name as doctor_name
                FROM visits v
                JOIN patients p ON v.patient_id = p.patient_id
                JOIN users u ON v.doctor_id = u.user_id
                WHERE v.visit_id = ?";
        return $this->db->fetchOne($sql, [$visitId]);
    }
    
    /**
     * Get visits by patient ID
     */
    public function getByPatientId(int $patientId, int $limit = 10): array {
        $sql = "SELECT v.*, u.full_name as doctor_name
                FROM visits v
                JOIN users u ON v.doctor_id = u.user_id
                WHERE v.patient_id = ?
                ORDER BY v.visit_date DESC, v.visit_time DESC
                LIMIT ?";
        return $this->db->fetchAll($sql, [$patientId, $limit]);
    }
    
    /**
     * Get today's visits for a doctor
     */
    public function getDoctorTodayVisits(int $doctorId): array {
        $sql = "SELECT v.*, 
                    p.patient_code, CONCAT(p.first_name, ' ', p.last_name) as patient_name
                FROM visits v
                JOIN patients p ON v.patient_id = p.patient_id
                WHERE v.doctor_id = ? AND v.visit_date = CURDATE()
                ORDER BY v.visit_time DESC";
        return $this->db->fetchAll($sql, [$doctorId]);
    }
    
    /**
     * Save vital signs
     */
    public function saveVitalSigns(array $data): int {
        // Check if vital signs already exist for this visit
        $existing = $this->db->fetchOne(
            "SELECT vital_id FROM vital_signs WHERE visit_id = ?", 
            [$data['visit_id']]
        );
        
        if ($existing) {
            // Update existing
            $sql = "UPDATE vital_signs SET
                        temperature = ?, blood_pressure_systolic = ?, blood_pressure_diastolic = ?,
                        pulse_rate = ?, respiratory_rate = ?, weight = ?, height = ?,
                        bmi = ?, oxygen_saturation = ?, notes = ?
                    WHERE visit_id = ?";
            
            $this->db->update($sql, [
                $data['temperature'] ?? null,
                $data['blood_pressure_systolic'] ?? null,
                $data['blood_pressure_diastolic'] ?? null,
                $data['pulse_rate'] ?? null,
                $data['respiratory_rate'] ?? null,
                $data['weight'] ?? null,
                $data['height'] ?? null,
                $data['bmi'] ?? null,
                $data['oxygen_saturation'] ?? null,
                $data['notes'] ?? null,
                $data['visit_id']
            ]);
            
            return $existing['vital_id'];
        } else {
            // Insert new
            $sql = "INSERT INTO vital_signs (
                        visit_id, patient_id, temperature, blood_pressure_systolic,
                        blood_pressure_diastolic, pulse_rate, respiratory_rate,
                        weight, height, bmi, oxygen_saturation, notes
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            return $this->db->insert($sql, [
                $data['visit_id'],
                $data['patient_id'],
                $data['temperature'] ?? null,
                $data['blood_pressure_systolic'] ?? null,
                $data['blood_pressure_diastolic'] ?? null,
                $data['pulse_rate'] ?? null,
                $data['respiratory_rate'] ?? null,
                $data['weight'] ?? null,
                $data['height'] ?? null,
                $data['bmi'] ?? null,
                $data['oxygen_saturation'] ?? null,
                $data['notes'] ?? null
            ]);
        }
    }
    
    /**
     * Get vital signs for a visit
     */
    public function getVitalSigns(int $visitId): ?array {
        $sql = "SELECT * FROM vital_signs WHERE visit_id = ?";
        return $this->db->fetchOne($sql, [$visitId]);
    }
    
    /**
     * Get patient's vital sign history
     */
    public function getVitalSignHistory(int $patientId, int $limit = 10): array {
        $sql = "SELECT vs.*, v.visit_date
                FROM vital_signs vs
                JOIN visits v ON vs.visit_id = v.visit_id
                WHERE vs.patient_id = ?
                ORDER BY v.visit_date DESC
                LIMIT ?";
        return $this->db->fetchAll($sql, [$patientId, $limit]);
    }
    
    /**
     * Get patient's most recent "In Progress" visit
     * Used to restore visit when doctor returns to a patient
     */
    public function getPatientInProgressVisit(int $patientId): ?array {
        $sql = "SELECT * FROM visits 
                WHERE patient_id = ? AND status = 'In Progress'
                ORDER BY created_at DESC, visit_id DESC
                LIMIT 1";
        return $this->db->fetchOne($sql, [$patientId]);
    }
}