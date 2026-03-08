<?php
/**
 * Medicine Class
 * Handles all medicine/drug management operations
 */

require_once __DIR__ . '/Database.php';

class Medicine {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // ==========================================
    // MEDICINE CRUD OPERATIONS
    // ==========================================
    
    /**
     * Generate unique medicine code
     */
    public function generateMedicineCode(): string {
        $sql = "SELECT MAX(medicine_id) as max_id FROM medicines";
        $result = $this->db->fetchOne($sql);
        $nextId = ($result['max_id'] ?? 0) + 1;
        return 'MED' . str_pad($nextId, 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Create new medicine
     */
    public function create(array $data): int {
        $sql = "INSERT INTO medicines (
                    medicine_code, medicine_name, main_category_id, sub_category1_id,
                    sub_category2_id, generic_id, trade_id, strength_value,
                    strength_unit_id, issuing_unit_id, mrp, dosage_form,
                    route, instructions, reorder_level, is_expiry_tracked,
                    discount_enabled, is_active
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        return $this->db->insert($sql, [
            $data['medicine_code'],
            $data['medicine_name'],
            $data['main_category_id'] ?: null,
            $data['sub_category1_id'] ?: null,
            $data['sub_category2_id'] ?: null,
            $data['generic_id'] ?: null,
            $data['trade_id'] ?: null,
            $data['strength_value'] ?: null,
            $data['strength_unit_id'] ?: null,
            $data['issuing_unit_id'] ?: null,
            $data['mrp'] ?? 0,
            $data['dosage_form'] ?: null,
            $data['route'] ?: null,
            $data['instructions'] ?: null,
            $data['reorder_level'] ?? 10,
            $data['is_expiry_tracked'] ?? 1,
            $data['discount_enabled'] ?? 0,
            $data['is_active'] ?? 1
        ]);
    }
    
    /**
     * Update medicine
     */
    public function update(int $medicineId, array $data): bool {
        $sql = "UPDATE medicines SET
                    medicine_name = ?, main_category_id = ?, sub_category1_id = ?,
                    sub_category2_id = ?, generic_id = ?, trade_id = ?,
                    strength_value = ?, strength_unit_id = ?, issuing_unit_id = ?,
                    mrp = ?, dosage_form = ?, route = ?, instructions = ?,
                    reorder_level = ?, is_expiry_tracked = ?, discount_enabled = ?,
                    is_active = ?, updated_at = NOW()
                WHERE medicine_id = ?";
        
        return $this->db->update($sql, [
            $data['medicine_name'],
            $data['main_category_id'] ?: null,
            $data['sub_category1_id'] ?: null,
            $data['sub_category2_id'] ?: null,
            $data['generic_id'] ?: null,
            $data['trade_id'] ?: null,
            $data['strength_value'] ?: null,
            $data['strength_unit_id'] ?: null,
            $data['issuing_unit_id'] ?: null,
            $data['mrp'] ?? 0,
            $data['dosage_form'] ?: null,
            $data['route'] ?: null,
            $data['instructions'] ?: null,
            $data['reorder_level'] ?? 10,
            $data['is_expiry_tracked'] ?? 1,
            $data['discount_enabled'] ?? 0,
            $data['is_active'] ?? 1,
            $medicineId
        ]) >= 0;
    }
    
    /**
     * Delete medicine (soft delete)
     */
    public function delete(int $medicineId): bool {
        $sql = "UPDATE medicines SET is_active = 0, updated_at = NOW() WHERE medicine_id = ?";
        return $this->db->update($sql, [$medicineId]) > 0;
    }
    
    /**
     * Permanently delete medicine
     */
    public function hardDelete(int $medicineId): bool {
        $sql = "DELETE FROM medicines WHERE medicine_id = ?";
        return $this->db->delete($sql, [$medicineId]) > 0;
    }
    
    /**
     * Get medicine by ID
     */
    public function getById(int $medicineId): ?array {
        $sql = "SELECT * FROM vw_medicines WHERE medicine_id = ?";
        return $this->db->fetchOne($sql, [$medicineId]);
    }
    
    /**
     * Get all medicines with pagination
     */
    public function getAll(int $offset = 0, int $limit = 20, bool $activeOnly = true): array {
        $sql = "SELECT * FROM vw_medicines";
        $params = [];
        
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        
        $sql .= " ORDER BY medicine_name ASC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Search medicines
     */
    public function search(string $term, int $limit = 20): array {
        $searchTerm = "%{$term}%";
        $sql = "SELECT * FROM vw_medicines 
                WHERE is_active = 1 AND (
                    medicine_code LIKE ? OR
                    medicine_name LIKE ? OR
                    generic_name LIKE ? OR
                    trade_name LIKE ?
                )
                ORDER BY medicine_name ASC
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [
            $searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit
        ]);
    }
    
    /**
     * Search for prescription autocomplete
     */
    public function searchForPrescription(string $term, int $limit = 15): array {
        $searchTerm = "%{$term}%";
        $sql = "SELECT 
                    medicine_id,
                    medicine_code,
                    medicine_name,
                    generic_name,
                    strength,
                    issuing_unit,
                    unit_symbol,
                    route
                FROM vw_medicines 
                WHERE is_active = 1 AND (
                    medicine_name LIKE ? OR
                    generic_name LIKE ? OR
                    trade_name LIKE ?
                )
                ORDER BY medicine_name ASC
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$searchTerm, $searchTerm, $searchTerm, $limit]);
    }
    
    /**
     * Get medicine count
     */
    public function getCount(bool $activeOnly = true): int {
        $sql = "SELECT COUNT(*) as count FROM medicines";
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        $result = $this->db->fetchOne($sql);
        return (int)($result['count'] ?? 0);
    }
    
    // ==========================================
    // CATEGORY OPERATIONS
    // ==========================================
    
    /**
     * Get all categories by level
     */
    public function getCategories(int $level = null, ?int $parentId = null): array {
        $sql = "SELECT * FROM drug_categories WHERE is_active = 1";
        $params = [];
        
        if ($level !== null) {
            $sql .= " AND category_level = ?";
            $params[] = $level;
        }
        
        if ($parentId !== null) {
            $sql .= " AND parent_id = ?";
            $params[] = $parentId;
        } elseif ($level === 1) {
            $sql .= " AND parent_id IS NULL";
        }
        
        $sql .= " ORDER BY category_name ASC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Get main categories
     */
    public function getMainCategories(): array {
        return $this->getCategories(1);
    }
    
    /**
     * Get sub categories by parent
     */
    public function getSubCategories(int $parentId): array {
        $sql = "SELECT * FROM drug_categories 
                WHERE parent_id = ? AND is_active = 1 
                ORDER BY category_name ASC";
        return $this->db->fetchAll($sql, [$parentId]);
    }
    
    /**
     * Add category
     */
    public function addCategory(string $name, ?int $parentId = null, int $level = 1, ?string $description = null): int {
        $sql = "INSERT INTO drug_categories (category_name, parent_id, category_level, description) 
                VALUES (?, ?, ?, ?)";
        return $this->db->insert($sql, [$name, $parentId, $level, $description]);
    }
    
    /**
     * Update category
     */
    public function updateCategory(int $categoryId, string $name, ?string $description = null): bool {
        $sql = "UPDATE drug_categories SET category_name = ?, description = ?, updated_at = NOW() 
                WHERE category_id = ?";
        return $this->db->update($sql, [$name, $description, $categoryId]) >= 0;
    }
    
    /**
     * Delete category
     */
    public function deleteCategory(int $categoryId): bool {
        $sql = "UPDATE drug_categories SET is_active = 0, updated_at = NOW() WHERE category_id = ?";
        return $this->db->update($sql, [$categoryId]) > 0;
    }
    
    // ==========================================
    // GENERIC NAME OPERATIONS
    // ==========================================
    
    /**
     * Get all generic names
     */
    public function getGenericNames(): array {
        $sql = "SELECT * FROM generic_names WHERE is_active = 1 ORDER BY generic_name ASC";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Search generic names
     */
    public function searchGenericNames(string $term, int $limit = 10): array {
        $sql = "SELECT * FROM generic_names 
                WHERE is_active = 1 AND generic_name LIKE ? 
                ORDER BY generic_name ASC LIMIT ?";
        return $this->db->fetchAll($sql, ["%{$term}%", $limit]);
    }
    
    /**
     * Add generic name
     */
    public function addGenericName(string $name, ?string $description = null): int {
        $sql = "INSERT INTO generic_names (generic_name, description) VALUES (?, ?)";
        return $this->db->insert($sql, [$name, $description]);
    }
    
    /**
     * Update generic name
     */
    public function updateGenericName(int $genericId, string $name, ?string $description = null): bool {
        $sql = "UPDATE generic_names SET generic_name = ?, description = ?, updated_at = NOW() 
                WHERE generic_id = ?";
        return $this->db->update($sql, [$name, $description, $genericId]) >= 0;
    }
    
    /**
     * Delete generic name
     */
    public function deleteGenericName(int $genericId): bool {
        $sql = "UPDATE generic_names SET is_active = 0, updated_at = NOW() WHERE generic_id = ?";
        return $this->db->update($sql, [$genericId]) > 0;
    }
    
    // ==========================================
    // TRADE NAME OPERATIONS
    // ==========================================
    
    /**
     * Get all trade names
     */
    public function getTradeNames(): array {
        $sql = "SELECT * FROM trade_names WHERE is_active = 1 ORDER BY trade_name ASC";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Add trade name
     */
    public function addTradeName(string $name, ?string $manufacturer = null): int {
        $sql = "INSERT INTO trade_names (trade_name, manufacturer) VALUES (?, ?)";
        return $this->db->insert($sql, [$name, $manufacturer]);
    }
    
    /**
     * Update trade name
     */
    public function updateTradeName(int $tradeId, string $name, ?string $manufacturer = null): bool {
        $sql = "UPDATE trade_names SET trade_name = ?, manufacturer = ?, updated_at = NOW() 
                WHERE trade_id = ?";
        return $this->db->update($sql, [$name, $manufacturer, $tradeId]) >= 0;
    }
    
    /**
     * Delete trade name
     */
    public function deleteTradeName(int $tradeId): bool {
        $sql = "UPDATE trade_names SET is_active = 0, updated_at = NOW() WHERE trade_id = ?";
        return $this->db->update($sql, [$tradeId]) > 0;
    }
    
    // ==========================================
    // ISSUING UNIT OPERATIONS
    // ==========================================
    
    /**
     * Get all issuing units
     */
    public function getIssuingUnits(): array {
        $sql = "SELECT * FROM issuing_units WHERE is_active = 1 ORDER BY unit_name ASC";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Add issuing unit
     */
    public function addIssuingUnit(string $name, ?string $symbol = null, ?string $description = null): int {
        $sql = "INSERT INTO issuing_units (unit_name, unit_symbol, description) VALUES (?, ?, ?)";
        return $this->db->insert($sql, [$name, $symbol, $description]);
    }
    
    /**
     * Update issuing unit
     */
    public function updateIssuingUnit(int $unitId, string $name, ?string $symbol = null, ?string $description = null): bool {
        $sql = "UPDATE issuing_units SET unit_name = ?, unit_symbol = ?, description = ? 
                WHERE unit_id = ?";
        return $this->db->update($sql, [$name, $symbol, $description, $unitId]) >= 0;
    }
    
    /**
     * Delete issuing unit
     */
    public function deleteIssuingUnit(int $unitId): bool {
        $sql = "UPDATE issuing_units SET is_active = 0 WHERE unit_id = ?";
        return $this->db->update($sql, [$unitId]) > 0;
    }
    
    // ==========================================
    // STRENGTH UNIT OPERATIONS
    // ==========================================
    
    /**
     * Get all strength units
     */
    public function getStrengthUnits(): array {
        $sql = "SELECT * FROM strength_units WHERE is_active = 1 ORDER BY unit_name ASC";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Add strength unit
     */
    public function addStrengthUnit(string $name, string $symbol): int {
        $sql = "INSERT INTO strength_units (unit_name, unit_symbol) VALUES (?, ?)";
        return $this->db->insert($sql, [$name, $symbol]);
    }
    
    // ==========================================
    // STATISTICS
    // ==========================================
    
    /**
     * Get medicine statistics
     */
    public function getStats(): array {
        $sql = "SELECT 
                    (SELECT COUNT(*) FROM medicines WHERE is_active = 1) as total_medicines,
                    (SELECT COUNT(*) FROM drug_categories WHERE is_active = 1 AND category_level = 1) as main_categories,
                    (SELECT COUNT(*) FROM generic_names WHERE is_active = 1) as generic_names,
                    (SELECT COUNT(*) FROM trade_names WHERE is_active = 1) as trade_names,
                    (SELECT COUNT(*) FROM issuing_units WHERE is_active = 1) as issuing_units";
        
        return $this->db->fetchOne($sql) ?? [];
    }
}