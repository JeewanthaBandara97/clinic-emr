<?php
/**
 * Test Class
 * Handles medical test requests
 */

require_once __DIR__ . '/Database.php';

class Test {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Create test request
     */
    public function create(array $data): int {
        $sql = "INSERT INTO tests (
                    visit_id, patient_id, test_name, test_type,
                    instructions, urgency, status
                ) VALUES (?, ?, ?, ?, ?, ?, 'Requested')";
        
        return $this->db->insert($sql, [
            $data['visit_id'],
            $data['patient_id'],
            $data['test_name'],
            $data['test_type'],
            $data['instructions'] ?? null,
            $data['urgency'] ?? 'Routine'
        ]);
    }
    
    /**
     * Create multiple tests
     */
    public function createMultiple(int $visitId, int $patientId, array $tests): array {
        $createdIds = [];
        
        foreach ($tests as $test) {
            if (!empty($test['test_type'])) {
                $id = $this->create([
                    'visit_id' => $visitId,
                    'patient_id' => $patientId,
                    'test_name' => $test['test_name'] ?? $test['test_type'],
                    'test_type' => $test['test_type'],
                    'instructions' => $test['instructions'] ?? null,
                    'urgency' => $test['urgency'] ?? 'Routine'
                ]);
                $createdIds[] = $id;
            }
        }
        
        return $createdIds;
    }
    
    /**
     * Get tests by visit ID
     */
    public function getByVisitId(int $visitId): array {
        $sql = "SELECT * FROM tests WHERE visit_id = ? ORDER BY requested_at DESC";
        return $this->db->fetchAll($sql, [$visitId]);
    }
    
    /**
     * Get tests by patient ID
     */
    public function getByPatientId(int $patientId, int $limit = 20): array {
        $sql = "SELECT t.*, v.visit_date 
                FROM tests t
                JOIN visits v ON t.visit_id = v.visit_id
                WHERE t.patient_id = ?
                ORDER BY t.requested_at DESC
                LIMIT ?";
        return $this->db->fetchAll($sql, [$patientId, $limit]);
    }
    
    /**
     * Update test status
     */
    public function updateStatus(int $testId, string $status, ?string $result = null): bool {
        $sql = "UPDATE tests SET status = ?";
        $params = [$status];
        
        if ($result !== null) {
            $sql .= ", result = ?, result_date = CURDATE()";
            $params[] = $result;
        }
        
        $sql .= " WHERE test_id = ?";
        $params[] = $testId;
        
        return $this->db->update($sql, $params) > 0;
    }
    
    /**
     * Delete test
     */
    public function delete(int $testId): bool {
        return $this->db->delete("DELETE FROM tests WHERE test_id = ?", [$testId]) > 0;
    }
}