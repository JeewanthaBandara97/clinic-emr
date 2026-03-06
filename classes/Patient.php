<?php
/**
 * Patient Class
 */

require_once __DIR__ . '/Database.php';

class Patient {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function generatePatientCode(): string {
        $sql = "SELECT MAX(patient_id) as max_id FROM patients";
        $result = $this->db->fetchOne($sql);
        $nextId = ($result['max_id'] ?? 0) + 1;
        return 'PAT' . str_pad($nextId, 6, '0', STR_PAD_LEFT);
    }
    
    public function create(array $data): int {
        $sql = "INSERT INTO patients (
                    patient_code, first_name, last_name, nic_number, date_of_birth,
                    gender, phone, address, blood_group, allergies, chronic_diseases,
                    weight, height, emergency_contact_name, emergency_contact_phone,
                    registration_date, registered_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        return $this->db->insert($sql, [
            $data['patient_code'],
            $data['first_name'],
            $data['last_name'],
            $data['nic_number'] ?: null,
            $data['date_of_birth'],
            $data['gender'],
            $data['phone'],
            $data['address'] ?: null,
            $data['blood_group'] ?: 'Unknown',
            $data['allergies'] ?: null,
            $data['chronic_diseases'] ?: null,
            $data['weight'] ?: null,
            $data['height'] ?: null,
            $data['emergency_contact_name'] ?: null,
            $data['emergency_contact_phone'] ?: null,
            $data['registration_date'],
            $data['registered_by']
        ]);
    }
    
    public function getById(int $patientId): ?array {
        $sql = "SELECT p.*, 
                    TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) AS age,
                    CONCAT(p.first_name, ' ', p.last_name) AS full_name
                FROM patients p 
                WHERE p.patient_id = ? AND p.is_active = 1";
        return $this->db->fetchOne($sql, [$patientId]);
    }
    
    public function getByCode(string $patientCode): ?array {
        $sql = "SELECT p.*, 
                    TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) AS age,
                    CONCAT(p.first_name, ' ', p.last_name) AS full_name
                FROM patients p 
                WHERE p.patient_code = ? AND p.is_active = 1";
        return $this->db->fetchOne($sql, [$patientCode]);
    }
    
    public function search(string $term, int $limit = 20): array {
        $searchTerm = "%{$term}%";
        $sql = "SELECT p.*, 
                    TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) AS age,
                    CONCAT(p.first_name, ' ', p.last_name) AS full_name
                FROM patients p 
                WHERE p.is_active = 1 AND (
                    p.patient_code LIKE ? OR
                    p.first_name LIKE ? OR
                    p.last_name LIKE ? OR
                    p.nic_number LIKE ? OR
                    p.phone LIKE ? OR
                    CONCAT(p.first_name, ' ', p.last_name) LIKE ?
                )
                ORDER BY p.first_name, p.last_name
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [
            $searchTerm, $searchTerm, $searchTerm, 
            $searchTerm, $searchTerm, $searchTerm, 
            $limit
        ]);
    }
    
    public function getAll(int $offset = 0, int $limit = 10): array {
        $sql = "SELECT p.*, 
                    TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) AS age,
                    CONCAT(p.first_name, ' ', p.last_name) AS full_name
                FROM patients p 
                WHERE p.is_active = 1 
                ORDER BY p.created_at DESC
                LIMIT ? OFFSET ?";
        return $this->db->fetchAll($sql, [$limit, $offset]);
    }
    
    public function getCount(): int {
        $sql = "SELECT COUNT(*) as count FROM patients WHERE is_active = 1";
        $result = $this->db->fetchOne($sql);
        return (int) $result['count'];
    }
    
    public function getTodaysPatients(): array {
        $sql = "SELECT p.*, 
                    TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) AS age,
                    CONCAT(p.first_name, ' ', p.last_name) AS full_name
                FROM patients p 
                WHERE p.is_active = 1 AND DATE(p.registration_date) = CURDATE()
                ORDER BY p.created_at DESC";
        return $this->db->fetchAll($sql);
    }
    
    public function nicExists(string $nic, ?int $excludeId = null): bool {
        $sql = "SELECT patient_id FROM patients WHERE nic_number = ? AND is_active = 1";
        $params = [$nic];
        
        if ($excludeId) {
            $sql .= " AND patient_id != ?";
            $params[] = $excludeId;
        }
        
        return $this->db->fetchOne($sql, $params) !== null;
    }
}