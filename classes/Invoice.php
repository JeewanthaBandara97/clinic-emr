<?php
/**
 * Invoice Class
 * Handles billing and invoice management
 */

require_once __DIR__ . '/Database.php';

class Invoice {
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
     * Generate invoice number
     */
    public function generateInvoiceNumber(): string {
        $prefix = 'INV-' . date('Y-m-d');
        $sql = "SELECT COUNT(*) as count FROM invoices WHERE DATE(invoice_date) = CURDATE()";
        $result = $this->db->fetchOne($sql);
        $seq = str_pad(($result['count'] + 1), 4, '0', STR_PAD_LEFT);
        return $prefix . '-' . $seq;
    }
    
    /**
     * Create invoice from visit
     */
    public function createFromVisit(int $visitId, array $items, array $data = []): int {
        try {
            $this->db->beginTransaction();
            
            $invoiceData = [
                'invoice_number' => $this->generateInvoiceNumber(),
                'visit_id' => $visitId,
                'patient_id' => $data['patient_id'],
                'doctor_id' => $data['doctor_id'],
                'invoice_date' => date('Y-m-d'),
                'subtotal' => 0,
                'tax_percentage' => $data['tax_percentage'] ?? 10,
                'discount_amount' => $data['discount_amount'] ?? 0,
                'created_by' => $data['created_by'] ?? null
            ];
            
            // Calculate totals
            $subtotal = 0;
            foreach ($items as $item) {
                $subtotal += ($item['unit_price'] * $item['quantity']);
            }
            
            $invoiceData['subtotal'] = $subtotal;
            $invoiceData['tax_amount'] = ($subtotal * $invoiceData['tax_percentage']) / 100;
            $invoiceData['total_amount'] = $subtotal + $invoiceData['tax_amount'] - $invoiceData['discount_amount'];
            $invoiceData['balance_due'] = $invoiceData['total_amount'];
            
            // Insert invoice
            $sql = "INSERT INTO invoices (
                        invoice_number, visit_id, patient_id, doctor_id, invoice_date,
                        subtotal, tax_amount, tax_percentage, discount_amount, total_amount,
                        balance_due, payment_status, created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $invoiceId = $this->db->insert($sql, [
                $invoiceData['invoice_number'],
                $invoiceData['visit_id'],
                $invoiceData['patient_id'],
                $invoiceData['doctor_id'],
                $invoiceData['invoice_date'],
                $invoiceData['subtotal'],
                $invoiceData['tax_amount'],
                $invoiceData['tax_percentage'],
                $invoiceData['discount_amount'],
                $invoiceData['total_amount'],
                $invoiceData['balance_due'],
                'Unpaid',
                $invoiceData['created_by']
            ]);
            
            // Insert line items
            foreach ($items as $item) {
                $itemSql = "INSERT INTO invoice_items (
                                invoice_id, item_type, item_description, item_reference_id,
                                quantity, unit_price, line_total
                            ) VALUES (?, ?, ?, ?, ?, ?, ?)";
                
                $this->db->insert($itemSql, [
                    $invoiceId,
                    $item['type'],
                    $item['description'],
                    $item['reference_id'] ?? null,
                    $item['quantity'],
                    $item['unit_price'],
                    $item['unit_price'] * $item['quantity']
                ]);
            }
            
            $this->db->commit();
            return $invoiceId;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Get invoice by ID
     */
    public function getById(int $invoiceId): ?array {
        $sql = "SELECT i.*, 
                    CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                    u.full_name as doctor_name
                FROM invoices i
                LEFT JOIN patients p ON i.patient_id = p.patient_id
                LEFT JOIN users u ON i.doctor_id = u.user_id
                WHERE i.invoice_id = ?";
        
        $invoice = $this->db->fetchOne($sql, [$invoiceId]);
        
        if ($invoice) {
            // Get line items
            $itemsSql = "SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY item_id";
            $invoice['items'] = $this->db->fetchAll($itemsSql, [$invoiceId]);
            
            // Get payments
            $paymentsSql = "SELECT * FROM payments WHERE invoice_id = ? ORDER BY payment_date DESC";
            $invoice['payments'] = $this->db->fetchAll($paymentsSql, [$invoiceId]);
        }
        
        return $invoice;
    }
    
    /**
     * Get invoice by number
     */
    public function getByNumber(string $invoiceNumber): ?array {
        $sql = "SELECT * FROM invoices WHERE invoice_number = ?";
        return $this->db->fetchOne($sql, [$invoiceNumber]);
    }
    
    /**
     * Get invoices for patient
     */
    public function getPatientInvoices(int $patientId): array {
        $sql = "SELECT i.*, 
                    (SELECT COUNT(*) FROM payments WHERE invoice_id = i.invoice_id) as payment_count
                FROM invoices i
                WHERE i.patient_id = ?
                ORDER BY i.invoice_date DESC";
        
        return $this->db->fetchAll($sql, [$patientId]);
    }
    
    /**
     * Record payment
     */
    public function recordPayment(int $invoiceId, array $paymentData): int {
        try {
            $this->db->beginTransaction();
            
            // Get current invoice
            $invoice = $this->getById($invoiceId);
            if (!$invoice) {
                throw new Exception('Invoice not found');
            }
            
            // Insert payment
            $sql = "INSERT INTO payments (
                        invoice_id, payment_date, amount_paid, payment_method,
                        reference_number, notes, recorded_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $paymentId = $this->db->insert($sql, [
                $invoiceId,
                $paymentData['payment_date'] ?? date('Y-m-d'),
                $paymentData['amount_paid'],
                $paymentData['payment_method'] ?? 'Cash',
                $paymentData['reference_number'] ?? null,
                $paymentData['notes'] ?? null,
                $paymentData['recorded_by'] ?? null
            ]);
            
            // Update invoice
            $newPaidAmount = ($invoice['paid_amount'] ?? 0) + $paymentData['amount_paid'];
            $newBalance = $invoice['total_amount'] - $newPaidAmount;
            $newStatus = $newBalance <= 0 ? 'Paid' : ($newPaidAmount > 0 ? 'Partially Paid' : 'Unpaid');
            
            $updateSql = "UPDATE invoices 
                         SET paid_amount = ?, balance_due = ?, payment_status = ?, updated_at = NOW()
                         WHERE invoice_id = ?";
            
            $this->db->update($updateSql, [
                $newPaidAmount,
                max(0, $newBalance),
                $newStatus,
                $invoiceId
            ]);
            
            $this->db->commit();
            return $paymentId;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Get outstanding invoices
     */
    public function getOutstandingInvoices(int $patientId = null, int $limit = 100): array {
        $sql = "SELECT i.*, CONCAT(p.first_name, ' ', p.last_name) as patient_name, COUNT(py.payment_id) as payment_count
                FROM invoices i
                LEFT JOIN patients p ON i.patient_id = p.patient_id
                LEFT JOIN payments py ON i.invoice_id = py.invoice_id
                WHERE i.payment_status IN ('Unpaid', 'Partially Paid')";
        
        $params = [];
        if ($patientId) {
            $sql .= " AND i.patient_id = ?";
            $params[] = $patientId;
        }
        
        $sql .= " GROUP BY i.invoice_id ORDER BY i.due_date ASC LIMIT ?";
        $params[] = $limit;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Get invoice statistics
     */
    public function getStatistics(string $startDate = null, string $endDate = null): array {
        $startDate = $startDate ?? date('Y-m-01');
        $endDate = $endDate ?? date('Y-m-d');
        
        $sql = "SELECT 
                    COUNT(*) as total_invoices,
                    SUM(total_amount) as total_revenue,
                    SUM(paid_amount) as total_paid,
                    SUM(balance_due) as total_outstanding,
                    COUNT(CASE WHEN payment_status = 'Paid' THEN 1 END) as paid_count,
                    COUNT(CASE WHEN payment_status = 'Partially Paid' THEN 1 END) as partial_paid_count,
                    COUNT(CASE WHEN payment_status = 'Unpaid' THEN 1 END) as unpaid_count
                FROM invoices
                WHERE DATE(invoice_date) BETWEEN ? AND ?";
        
        return [$this->db->fetchOne($sql, [$startDate, $endDate])];
    }
    
    /**
     * Cancel invoice
     */
    public function cancel(int $invoiceId, string $reason = ''): bool {
        $sql = "UPDATE invoices 
                SET payment_status = 'Cancelled', notes = CONCAT(notes, '\n\nCancelled: ', ?)
                WHERE invoice_id = ?";
        
        return $this->db->update($sql, [$reason, $invoiceId]) > 0;
    }
}
