<?php
/**
 * Prescription Class
 * Handles prescriptions and medicines
 */

require_once __DIR__ . '/Database.php';

class Prescription {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Generate unique prescription code
     */
    public function generatePrescriptionCode(): string {
        $date = date('Ymd');
        $sql = "SELECT COUNT(*) as count FROM patient_prescriptions WHERE prescription_date = CURDATE()";
        $result = $this->db->fetchOne($sql);
        $count = ($result['count'] ?? 0) + 1;
        return 'RX' . $date . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Create prescription
     */
    public function create(array $data): int {
        $sql = "INSERT INTO patient_prescriptions (
                    prescription_code, visit_id, patient_id, doctor_id,
                    prescription_date, notes, status
                ) VALUES (?, ?, ?, ?, ?, ?, 'Active')";
        
        return $this->db->insert($sql, [
            $data['prescription_code'],
            $data['visit_id'],
            $data['patient_id'],
            $data['doctor_id'],
            $data['prescription_date'],
            $data['notes'] ?? null
        ]);
    }
    
    /**
     * Add medicine to prescription
     */
    public function addMedicine(int $prescriptionId, array $medicine): int {
        $sql = "INSERT INTO patient_prescription_medicines (
                    prescription_id, medicine_name, dose, frequency,
                    duration_days, quantity, route, instructions
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        return $this->db->insert($sql, [
            $prescriptionId,
            $medicine['medicine_name'],
            $medicine['dose'],
            $medicine['frequency'],
            $medicine['duration_days'],
            $medicine['quantity'] ?? null,
            $medicine['route'] ?? 'Oral',
            $medicine['instructions'] ?? null
        ]);
    }
    
    /**
     * Add multiple medicines
     */
    public function addMedicines(int $prescriptionId, array $medicines): array {
        $createdIds = [];
        
        foreach ($medicines as $medicine) {
            if (!empty($medicine['medicine_name'])) {
                $id = $this->addMedicine($prescriptionId, $medicine);
                $createdIds[] = $id;
            }
        }
        
        return $createdIds;
    }
    
    /**
     * Get prescription by ID with all details
     */
    public function getById(int $prescriptionId): ?array {
        $sql = "SELECT pr.*, 
                    p.patient_code, p.first_name, p.last_name,
                    CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                    TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) as patient_age,
                    p.gender as patient_gender, p.phone as patient_phone,
                    p.address as patient_address, p.allergies,
                    u.full_name as doctor_name,
                    dd.specialization, dd.qualification, dd.license_number,
                    v.diagnosis, v.symptoms, v.notes as clinical_notes, v.follow_up_date
                FROM patient_prescriptions pr
                JOIN patients p ON pr.patient_id = p.patient_id
                JOIN users u ON pr.doctor_id = u.user_id
                LEFT JOIN doctor_details dd ON u.user_id = dd.user_id
                JOIN patient_visits v ON pr.visit_id = v.visit_id
                WHERE pr.prescription_id = ?";
        
        return $this->db->fetchOne($sql, [$prescriptionId]);
    }
    
    /**
     * Get prescription by visit ID
     */
    public function getByVisitId(int $visitId): ?array {
        $sql = "SELECT * FROM patient_prescriptions WHERE visit_id = ?";
        return $this->db->fetchOne($sql, [$visitId]);
    }
    
    /**
     * Get medicines for a prescription
     */
    public function getMedicines(int $prescriptionId): array {
        $sql = "SELECT * FROM patient_prescription_medicines WHERE prescription_id = ? ORDER BY medicine_id";
        return $this->db->fetchAll($sql, [$prescriptionId]);
    }
    
    /**
     * Get prescriptions by patient ID
     */
    public function getByPatientId(int $patientId, int $limit = 10): array {
        $sql = "SELECT pr.*, u.full_name as doctor_name
                FROM patient_prescriptions pr
                JOIN users u ON pr.doctor_id = u.user_id
                WHERE pr.patient_id = ?
                ORDER BY pr.prescription_date DESC
                LIMIT ?";
        return $this->db->fetchAll($sql, [$patientId, $limit]);
    }
    
    /**
     * Delete medicine from prescription
     */
    public function deleteMedicine(int $medicineId): bool {
        return $this->db->delete("DELETE FROM patient_prescription_medicines WHERE medicine_id = ?", [$medicineId]) > 0;
    }
    
    /**
     * Get full prescription details for printing
     */
    public function getFullPrescription(int $prescriptionId): ?array {
        $prescription = $this->getById($prescriptionId);
        
        if (!$prescription) {
            return null;
        }
        
        $prescription['medicines'] = $this->getMedicines($prescriptionId);
        
        // Get tests for this visit
        $testObj = new Test();
        $prescription['tests'] = $testObj->getByVisitId($prescription['visit_id']);
        
        // Get vital signs
        $visitObj = new Visit();
        $prescription['vital_signs'] = $visitObj->getVitalSigns($prescription['visit_id']);
        
        return $prescription;
    }
}
