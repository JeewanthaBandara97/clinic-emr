<?php
/**
 * Inventory Class
 * Handles medicine stock management and tracking
 */

require_once __DIR__ . '/Database.php';

class Inventory {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get database instance
     */
    public function getDb() {
        return $this->db;
    }
    
    /**
     * Initialize stock for a medicine
     */
    public function initializeStock(int $medicineId, int $initialQuantity = 0, int $reorderLevel = 10): bool {
        $sql = "INSERT INTO medicine_stock (medicine_id, current_quantity, reorder_level, reorder_quantity)
                VALUES (?, ?, ?, 50)
                ON DUPLICATE KEY UPDATE current_quantity = ?, reorder_level = ?";
        
        return $this->db->update($sql, [
            $medicineId, $initialQuantity, $reorderLevel, $initialQuantity, $reorderLevel
        ]) > 0;
    }
    
    /**
     * Get stock for medicine
     */
    public function getStock(int $medicineId): ?array {
        $sql = "SELECT ms.*, m.medicine_name, m.mrp
                FROM medicine_stock ms
                JOIN medicines m ON ms.medicine_id = m.medicine_id
                WHERE ms.medicine_id = ?";
        
        return $this->db->fetchOne($sql, [$medicineId]);
    }
    
    /**
     * Update stock quantity
     */
    public function updateStock(int $medicineId, int $quantityChange, string $transactionType, 
                               array $data = []): int {
        try {
            $this->db->beginTransaction();
            
            // Get current stock
            $stock = $this->getStock($medicineId);
            if (!$stock) {
                throw new Exception('Stock record not found');
            }
            
            $newQuantity = $stock['current_quantity'] + $quantityChange;
            
            // Prevent negative stock for sales
            if ($transactionType === 'Sale' && $newQuantity < 0) {
                throw new Exception('Insufficient stock for this transaction');
            }
            
            // Update stock
            $updateSql = "UPDATE medicine_stock 
                         SET current_quantity = ?, last_updated = NOW()
                         WHERE medicine_id = ?";
            
            $this->db->update($updateSql, [$newQuantity, $medicineId]);
            
            // Record transaction
            $transactionSql = "INSERT INTO stock_transactions (
                                    medicine_id, transaction_type, quantity, unit_cost,
                                    transaction_date, notes, recorded_by, reference_id
                                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $transactionId = $this->db->insert($transactionSql, [
                $medicineId,
                $transactionType,
                $quantityChange,
                $data['unit_cost'] ?? null,
                $data['transaction_date'] ?? date('Y-m-d'),
                $data['notes'] ?? null,
                $data['recorded_by'] ?? null,
                $data['reference_id'] ?? null
            ]);
            
            $this->db->commit();
            return $transactionId;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Record medicine purchase
     */
    public function recordPurchase(int $medicineId, int $quantity, decimal $unitCost, 
                                   string $batchNumber = '', date $expiryDate = null): int {
        try {
            $this->db->beginTransaction();
            
            // Update stock
            $this->updateStock($medicineId, $quantity, 'Purchase', [
                'unit_cost' => $unitCost,
                'transaction_date' => date('Y-m-d'),
                'notes' => "Batch: $batchNumber"
            ]);
            
            // Record batch if expiry tracking
            if ($expiryDate) {
                $batchSql = "INSERT INTO medicine_expiry_batches (
                                medicine_id, batch_number, quantity, cost_per_unit,
                                manufacturing_date, expiry_date
                            ) VALUES (?, ?, ?, ?, ?, ?)";
                
                $this->db->insert($batchSql, [
                    $medicineId,
                    $batchNumber,
                    $quantity,
                    $unitCost,
                    date('Y-m-d'),
                    $expiryDate
                ]);
            }
            
            $stock = $this->getStock($medicineId);
            
            $this->db->commit();
            return $stock['stock_id'];
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Record medicine sale (for pharmacy)
     */
    public function recordSale(int $medicineId, int $quantity): int {
        return $this->updateStock($medicineId, -$quantity, 'Sale', [
            'transaction_date' => date('Y-m-d')
        ]);
    }
    
    /**
     * Get medicines with low stock
     */
    public function getLowStockMedicines(): array {
        $sql = "SELECT ms.*, m.medicine_name, m.mrp,
                    (ms.reorder_level - ms.current_quantity) as shortage_qty
                FROM medicine_stock ms
                JOIN medicines m ON ms.medicine_id = m.medicine_id
                WHERE ms.current_quantity <= ms.reorder_level
                AND m.is_active = 1
                ORDER BY ms.current_quantity ASC";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Get expiring medicines (within 30 days)
     */
    public function getExpiringMedicines(int $daysWarning = 30): array {
        $expiryDate = date('Y-m-d', strtotime("+$daysWarning days"));
        
        $sql = "SELECT mb.*, m.medicine_name, m.mrp
                FROM medicine_expiry_batches mb
                JOIN medicines m ON mb.medicine_id = m.medicine_id
                WHERE mb.expiry_date BETWEEN CURDATE() AND ?
                AND mb.status = 'Active'
                AND mb.quantity > 0
                ORDER BY mb.expiry_date ASC";
        
        return $this->db->fetchAll($sql, [$expiryDate]);
    }
    
    /**
     * Get expired medicines
     */
    public function getExpiredMedicines(): array {
        $sql = "SELECT mb.*, m.medicine_name, m.mrp
                FROM medicine_expiry_batches mb
                JOIN medicines m ON mb.medicine_id = m.medicine_id
                WHERE mb.expiry_date < CURDATE()
                AND mb.status = 'Active'
                ORDER BY mb.expiry_date DESC";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Mark batch as expired
     */
    public function markBatchExpired(int $batchId): bool {
        $batch = $this->db->fetchOne("SELECT * FROM medicine_expiry_batches WHERE batch_id = ?", [$batchId]);
        
        if (!$batch) {
            return false;
        }
        
        try {
            $this->db->beginTransaction();
            
            // Record adjustment
            $this->updateStock($batch['medicine_id'], -$batch['quantity'], 'Expiry', [
                'notes' => "Batch $batch[batch_number] expired on $batch[expiry_date]"
            ]);
            
            // Mark batch as expired
            $this->db->update(
                "UPDATE medicine_expiry_batches SET status = 'Expired' WHERE batch_id = ?",
                [$batchId]
            );
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Get stock history for medicine
     */
    public function getStockHistory(int $medicineId, int $limit = 100): array {
        $sql = "SELECT st.*, u.full_name as recorded_by_name
                FROM stock_transactions st
                LEFT JOIN users u ON st.recorded_by = u.user_id
                WHERE st.medicine_id = ?
                ORDER BY st.created_at DESC
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$medicineId, $limit]);
    }
    
    /**
     * Get stock summary (all medicines)
     */
    public function getStockSummary(): array {
        $sql = "SELECT 
                    COUNT(*) as total_medicines,
                    SUM(ms.current_quantity) as total_quantity,
                    COUNT(CASE WHEN ms.current_quantity <= ms.reorder_level THEN 1 END) as low_stock_count,
                    COUNT(CASE WHEN ms.current_quantity = 0 THEN 1 END) as out_of_stock_count
                FROM medicine_stock ms
                JOIN medicines m ON ms.medicine_id = m.medicine_id
                WHERE m.is_active = 1";
        
        return $this->db->fetchOne($sql);
    }
    
    /**
     * Get stock valuation (total cost of inventory)
     */
    public function getInventoryValuation(): ?array {
        $sql = "SELECT 
                    SUM(ms.current_quantity * ms.unit_cost) as total_cost_value,
                    SUM(ms.current_quantity * m.mrp) as total_retail_value,
                    COUNT(*) as total_items
                FROM medicine_stock ms
                JOIN medicines m ON ms.medicine_id = m.medicine_id
                WHERE m.is_active = 1";
        
        return $this->db->fetchOne($sql);
    }
    
    /**
     * Generate reorder list
     */
    public function getReorderList(): array {
        $sql = "SELECT ms.*, m.medicine_name, m.medicine_code,
                    (ms.reorder_quantity - ms.current_quantity) as order_qty,
                    (ms.reorder_quantity - ms.current_quantity) * ms.unit_cost as order_cost
                FROM medicine_stock ms
                JOIN medicines m ON ms.medicine_id = m.medicine_id
                WHERE ms.current_quantity < ms.reorder_level
                AND m.is_active = 1
                ORDER BY (ms.reorder_level - ms.current_quantity) DESC";
        
        return $this->db->fetchAll($sql);
    }
}
