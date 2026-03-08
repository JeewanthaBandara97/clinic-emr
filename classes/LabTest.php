<?php
/**
 * LabTest Class
 * Handles all lab test and test type management operations
 */

require_once __DIR__ . '/Database.php';

class LabTest {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // ==========================================
    // TEST TYPE CRUD OPERATIONS
    // ==========================================
    
    /**
     * Get all test types
     */
    public function getTestTypes(bool $activeOnly = true): array {
        $sql = "SELECT 
                    tt.*,
                    COUNT(lt.test_id) as test_count,
                    SUM(CASE WHEN lt.is_active = 1 THEN 1 ELSE 0 END) as active_tests
                FROM test_types tt
                LEFT JOIN lab_tests lt ON tt.type_id = lt.type_id";
        
        if ($activeOnly) {
            $sql .= " WHERE tt.is_active = 1";
        }
        
        $sql .= " GROUP BY tt.type_id
                  ORDER BY tt.display_order ASC, tt.type_name ASC";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Get test type by ID
     */
    public function getTestTypeById(int $typeId): ?array {
        $sql = "SELECT * FROM test_types WHERE type_id = ?";
        return $this->db->fetchOne($sql, [$typeId]);
    }
    
    /**
     * Get simple type list for dropdowns
     */
    public function getTypeList(bool $activeOnly = true): array {
        $sql = "SELECT type_id, type_name FROM test_types";
        
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        
        $sql .= " ORDER BY display_order ASC, type_name ASC";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Check if test type name exists
     */
    public function typeNameExists(string $name, ?int $excludeId = null): bool {
        $sql = "SELECT type_id FROM test_types WHERE type_name = ?";
        $params = [$name];
        
        if ($excludeId) {
            $sql .= " AND type_id != ?";
            $params[] = $excludeId;
        }
        
        return $this->db->fetchOne($sql, $params) !== null;
    }
    
    /**
     * Add test type
     */
    public function addTestType(string $name, int $displayOrder = 0): int {
        if ($this->typeNameExists($name)) {
            throw new Exception('Test type already exists.');
        }
        
        $sql = "INSERT INTO test_types (type_name, display_order) VALUES (?, ?)";
        return $this->db->insert($sql, [$name, $displayOrder]);
    }
    
    /**
     * Update test type
     */
    public function updateTestType(int $typeId, string $name, int $displayOrder = 0): bool {
        if ($this->typeNameExists($name, $typeId)) {
            throw new Exception('Test type name already exists.');
        }
        
        $sql = "UPDATE test_types SET type_name = ?, display_order = ? WHERE type_id = ?";
        return $this->db->update($sql, [$name, $displayOrder, $typeId]) >= 0;
    }
    
    /**
     * Delete test type
     */
    public function deleteTestType(int $typeId): bool {
        // Check if tests exist under this type
        $testCount = $this->getTestCountByType($typeId);
        if ($testCount > 0) {
            throw new Exception("Cannot delete. {$testCount} test(s) exist under this type.");
        }
        
        $sql = "DELETE FROM test_types WHERE type_id = ?";
        return $this->db->delete($sql, [$typeId]) > 0;
    }
    
    /**
     * Soft delete test type
     */
    public function deactivateTestType(int $typeId): bool {
        $sql = "UPDATE test_types SET is_active = 0 WHERE type_id = ?";
        return $this->db->update($sql, [$typeId]) > 0;
    }
    
    /**
     * Activate test type
     */
    public function activateTestType(int $typeId): bool {
        $sql = "UPDATE test_types SET is_active = 1 WHERE type_id = ?";
        return $this->db->update($sql, [$typeId]) > 0;
    }
    
    /**
     * Get test count by type
     */
    public function getTestCountByType(int $typeId): int {
        $sql = "SELECT COUNT(*) as cnt FROM lab_tests WHERE type_id = ?";
        $result = $this->db->fetchOne($sql, [$typeId]);
        return (int)($result['cnt'] ?? 0);
    }
    
    /**
     * Update display order for multiple types
     */
    public function updateTypeOrder(array $orders): bool {
        foreach ($orders as $typeId => $order) {
            $sql = "UPDATE test_types SET display_order = ? WHERE type_id = ?";
            $this->db->update($sql, [(int)$order, (int)$typeId]);
        }
        return true;
    }
    
    // ==========================================
    // LAB TEST CRUD OPERATIONS
    // ==========================================
    
    /**
     * Get all lab tests with type info
     */
    public function getLabTests(bool $activeOnly = false, ?int $typeId = null): array {
        $sql = "SELECT 
                    lt.*,
                    tt.type_name
                FROM lab_tests lt
                JOIN test_types tt ON lt.type_id = tt.type_id
                WHERE 1=1";
        $params = [];
        
        if ($activeOnly) {
            $sql .= " AND lt.is_active = 1";
        }
        
        if ($typeId) {
            $sql .= " AND lt.type_id = ?";
            $params[] = $typeId;
        }
        
        $sql .= " ORDER BY tt.display_order ASC, lt.test_name ASC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Get lab tests with pagination
     */
    public function getLabTestsPaginated(
        int $offset = 0, 
        int $limit = 20, 
        bool $activeOnly = false,
        ?int $typeId = null,
        ?string $search = null
    ): array {
        $sql = "SELECT 
                    lt.*,
                    tt.type_name
                FROM lab_tests lt
                JOIN test_types tt ON lt.type_id = tt.type_id
                WHERE 1=1";
        $params = [];
        
        if ($activeOnly) {
            $sql .= " AND lt.is_active = 1";
        }
        
        if ($typeId) {
            $sql .= " AND lt.type_id = ?";
            $params[] = $typeId;
        }
        
        if ($search) {
            $sql .= " AND (lt.test_name LIKE ? OR tt.type_name LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $sql .= " ORDER BY tt.display_order ASC, lt.test_name ASC
                  LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Get lab test by ID
     */
    public function getLabTestById(int $testId): ?array {
        $sql = "SELECT 
                    lt.*,
                    tt.type_name
                FROM lab_tests lt
                JOIN test_types tt ON lt.type_id = tt.type_id
                WHERE lt.test_id = ?";
        return $this->db->fetchOne($sql, [$testId]);
    }
    
    /**
     * Check if test name exists under type
     */
    public function testNameExists(string $name, int $typeId, ?int $excludeId = null): bool {
        $sql = "SELECT test_id FROM lab_tests WHERE test_name = ? AND type_id = ?";
        $params = [$name, $typeId];
        
        if ($excludeId) {
            $sql .= " AND test_id != ?";
            $params[] = $excludeId;
        }
        
        return $this->db->fetchOne($sql, $params) !== null;
    }
    
    /**
     * Add lab test
     */
    public function addLabTest(int $typeId, string $name, float $price = 0): int {
        if ($this->testNameExists($name, $typeId)) {
            throw new Exception('Test already exists under this type.');
        }
        
        $sql = "INSERT INTO lab_tests (type_id, test_name, test_price) VALUES (?, ?, ?)";
        return $this->db->insert($sql, [$typeId, $name, $price]);
    }
    
    /**
     * Update lab test
     */
    public function updateLabTest(int $testId, array $data): bool {
        $typeId = (int)($data['type_id'] ?? 0);
        $name = trim($data['test_name'] ?? '');
        $price = (float)($data['test_price'] ?? 0);
        $isActive = isset($data['is_active']) ? (int)$data['is_active'] : 1;
        
        if (empty($name)) {
            throw new Exception('Test name is required.');
        }
        
        if ($typeId <= 0) {
            throw new Exception('Test type is required.');
        }
        
        // Check duplicate (excluding current test)
        $existing = $this->db->fetchOne(
            "SELECT test_id FROM lab_tests WHERE test_name = ? AND type_id = ? AND test_id != ?",
            [$name, $typeId, $testId]
        );
        if ($existing) {
            throw new Exception('Test name already exists under this type.');
        }
        
        $sql = "UPDATE lab_tests 
                SET type_id = ?, test_name = ?, test_price = ?, is_active = ? 
                WHERE test_id = ?";
        return $this->db->update($sql, [$typeId, $name, $price, $isActive, $testId]) >= 0;
    }
    
    /**
     * Delete lab test
     */
    public function deleteLabTest(int $testId): bool {
        $sql = "DELETE FROM lab_tests WHERE test_id = ?";
        return $this->db->delete($sql, [$testId]) > 0;
    }
    
    /**
     * Soft delete lab test
     */
    public function deactivateLabTest(int $testId): bool {
        $sql = "UPDATE lab_tests SET is_active = 0 WHERE test_id = ?";
        return $this->db->update($sql, [$testId]) > 0;
    }
    
    /**
     * Activate lab test
     */
    public function activateLabTest(int $testId): bool {
        $sql = "UPDATE lab_tests SET is_active = 1 WHERE test_id = ?";
        return $this->db->update($sql, [$testId]) > 0;
    }
    
    /**
     * Toggle lab test status
     */
    public function toggleLabTestStatus(int $testId, int $status): bool {
        $sql = "UPDATE lab_tests SET is_active = ? WHERE test_id = ?";
        return $this->db->update($sql, [$status, $testId]) >= 0;
    }
    
    /**
     * Update test price
     */
    public function updateTestPrice(int $testId, float $price): bool {
        $sql = "UPDATE lab_tests SET test_price = ? WHERE test_id = ?";
        return $this->db->update($sql, [$price, $testId]) >= 0;
    }
    
    /**
     * Bulk update prices by percentage
     */
    public function bulkUpdatePrices(float $percentage, ?int $typeId = null): int {
        $sql = "UPDATE lab_tests SET test_price = test_price * (1 + ? / 100)";
        $params = [$percentage];
        
        if ($typeId) {
            $sql .= " WHERE type_id = ?";
            $params[] = $typeId;
        }
        
        return $this->db->update($sql, $params);
    }
    
    // ==========================================
    // SEARCH OPERATIONS
    // ==========================================
    
    /**
     * Search lab tests
     */
    public function search(string $term, int $limit = 20, bool $activeOnly = true): array {
        $searchTerm = "%{$term}%";
        
        $sql = "SELECT 
                    lt.*,
                    tt.type_name
                FROM lab_tests lt
                JOIN test_types tt ON lt.type_id = tt.type_id
                WHERE (lt.test_name LIKE ? OR tt.type_name LIKE ?)";
        $params = [$searchTerm, $searchTerm];
        
        if ($activeOnly) {
            $sql .= " AND lt.is_active = 1 AND tt.is_active = 1";
        }
        
        $sql .= " ORDER BY lt.test_name ASC LIMIT ?";
        $params[] = $limit;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Search for autocomplete (minimal data)
     */
    public function searchForAutocomplete(string $term, int $limit = 10): array {
        $searchTerm = "%{$term}%";
        
        $sql = "SELECT 
                    lt.test_id,
                    lt.test_name,
                    lt.test_price,
                    tt.type_name
                FROM lab_tests lt
                JOIN test_types tt ON lt.type_id = tt.type_id
                WHERE lt.is_active = 1 
                  AND tt.is_active = 1 
                  AND (lt.test_name LIKE ? OR tt.type_name LIKE ?)
                ORDER BY lt.test_name ASC 
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$searchTerm, $searchTerm, $limit]);
    }
    
    // ==========================================
    // COUNT & STATISTICS
    // ==========================================
    
    /**
     * Get total test count
     */
    public function getTestCount(bool $activeOnly = false, ?int $typeId = null, ?string $search = null): int {
        $sql = "SELECT COUNT(*) as cnt 
                FROM lab_tests lt
                JOIN test_types tt ON lt.type_id = tt.type_id
                WHERE 1=1";
        $params = [];
        
        if ($activeOnly) {
            $sql .= " AND lt.is_active = 1";
        }
        
        if ($typeId) {
            $sql .= " AND lt.type_id = ?";
            $params[] = $typeId;
        }
        
        if ($search) {
            $sql .= " AND (lt.test_name LIKE ? OR tt.type_name LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $result = $this->db->fetchOne($sql, $params);
        return (int)($result['cnt'] ?? 0);
    }
    
    /**
     * Get statistics
     */
    public function getStats(): array {
        $sql = "SELECT 
                    (SELECT COUNT(*) FROM test_types WHERE is_active = 1) as total_types,
                    (SELECT COUNT(*) FROM lab_tests) as total_tests,
                    (SELECT COUNT(*) FROM lab_tests WHERE is_active = 1) as active_tests,
                    (SELECT COUNT(*) FROM lab_tests WHERE is_active = 0) as inactive_tests,
                    (SELECT AVG(test_price) FROM lab_tests WHERE is_active = 1) as avg_price,
                    (SELECT MIN(test_price) FROM lab_tests WHERE is_active = 1 AND test_price > 0) as min_price,
                    (SELECT MAX(test_price) FROM lab_tests WHERE is_active = 1) as max_price";
        
        $result = $this->db->fetchOne($sql);
        
        return [
            'total_types' => (int)($result['total_types'] ?? 0),
            'total_tests' => (int)($result['total_tests'] ?? 0),
            'active_tests' => (int)($result['active_tests'] ?? 0),
            'inactive_tests' => (int)($result['inactive_tests'] ?? 0),
            'avg_price' => round((float)($result['avg_price'] ?? 0), 2),
            'min_price' => (float)($result['min_price'] ?? 0),
            'max_price' => (float)($result['max_price'] ?? 0),
        ];
    }
    
    /**
     * Get tests grouped by type
     */
    public function getTestsGroupedByType(bool $activeOnly = true): array {
        $types = $this->getTestTypes($activeOnly);
        $result = [];
        
        foreach ($types as $type) {
            $tests = $this->getLabTests($activeOnly, $type['type_id']);
            $result[] = [
                'type' => $type,
                'tests' => $tests
            ];
        }
        
        return $result;
    }
    
    // ==========================================
    // IMPORT/EXPORT HELPERS
    // ==========================================
    
    /**
     * Get all tests for export
     */
    public function exportTests(): array {
        $sql = "SELECT 
                    lt.test_id,
                    tt.type_name,
                    lt.test_name,
                    lt.test_price,
                    CASE WHEN lt.is_active = 1 THEN 'Active' ELSE 'Inactive' END as status
                FROM lab_tests lt
                JOIN test_types tt ON lt.type_id = tt.type_id
                ORDER BY tt.display_order ASC, lt.test_name ASC";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Import tests from array
     */
    public function importTests(array $tests): array {
        $success = 0;
        $failed = 0;
        $errors = [];
        
        foreach ($tests as $index => $test) {
            try {
                $typeName = trim($test['type_name'] ?? '');
                $testName = trim($test['test_name'] ?? '');
                $price = (float)($test['test_price'] ?? 0);
                
                if (empty($typeName) || empty($testName)) {
                    throw new Exception('Type name and test name are required.');
                }
                
                // Get or create type
                $type = $this->db->fetchOne(
                    "SELECT type_id FROM test_types WHERE type_name = ?",
                    [$typeName]
                );
                
                if (!$type) {
                    $typeId = $this->addTestType($typeName);
                } else {
                    $typeId = $type['type_id'];
                }
                
                // Add test if not exists
                if (!$this->testNameExists($testName, $typeId)) {
                    $this->addLabTest($typeId, $testName, $price);
                    $success++;
                } else {
                    $failed++;
                    $errors[] = "Row " . ($index + 1) . ": Test '{$testName}' already exists.";
                }
                
            } catch (Exception $e) {
                $failed++;
                $errors[] = "Row " . ($index + 1) . ": " . $e->getMessage();
            }
        }
        
        return [
            'success' => $success,
            'failed' => $failed,
            'errors' => $errors
        ];
    }
    
    // ==========================================
    // VALIDATION HELPERS
    // ==========================================
    
    /**
     * Validate test data
     */
    public function validateTest(array $data): array {
        $errors = [];
        
        if (empty(trim($data['test_name'] ?? ''))) {
            $errors['test_name'] = 'Test name is required.';
        }
        
        if (empty($data['type_id']) || $data['type_id'] <= 0) {
            $errors['type_id'] = 'Test type is required.';
        }
        
        $price = $data['test_price'] ?? 0;
        if (!is_numeric($price) || $price < 0) {
            $errors['test_price'] = 'Price must be a valid positive number.';
        }
        
        return $errors;
    }
    
    /**
     * Validate test type data
     */
    public function validateTestType(array $data): array {
        $errors = [];
        
        if (empty(trim($data['type_name'] ?? ''))) {
            $errors['type_name'] = 'Type name is required.';
        }
        
        $order = $data['display_order'] ?? 0;
        if (!is_numeric($order) || $order < 0) {
            $errors['display_order'] = 'Display order must be a valid positive number.';
        }
        
        return $errors;
    }
}