<?php
/**
 * Assistant - Invoices & Billing
 * Manage patient invoices and payments
 */

$pageTitle = 'Invoices & Billing';
$currentPage = 'invoices';

require_once __DIR__ . '/../includes/auth.php';
requireAssistant();
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../classes/Invoice.php';

$invoice = new Invoice();
$errors = [];

// Handle payment recording - BEFORE header output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'record_payment') {
    checkCSRF();
    
    $invoiceId = (int)$_POST['invoice_id'];
    $amountPaid = (float)$_POST['amount_paid'];
    
    try {
        $invoice->recordPayment($invoiceId, [
            'amount_paid' => $amountPaid,
            'payment_method' => $_POST['payment_method'] ?? 'Cash',
            'payment_date' => $_POST['payment_date'] ?? date('Y-m-d'),
            'reference_number' => $_POST['reference_number'] ?? null,
            'notes' => $_POST['notes'] ?? null,
            'recorded_by' => User::getUserId()
        ]);
        
        setFlash('success', 'Payment recorded successfully!');
        redirect(APP_URL . '/assistant/invoices.php?id=' . $invoiceId);
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
}

// NOW include headers after POST processing
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$invoiceId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$viewInvoice = null;

// Get invoice if ID provided
if ($invoiceId) {
    $viewInvoice = $invoice->getById($invoiceId);
    if (!$viewInvoice) {
        redirect(APP_URL . '/assistant/invoices.php', 'danger', 'Invoice not found.');
    }
}
?>

<div class="page-header">
    <h1><i class="bi bi-receipt me-2"></i>Invoices & Billing</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Invoices</li>
        </ol>
    </nav>
</div>

<?php displayFlash(); ?>

<?php if ($viewInvoice): ?>
    <!-- Invoice Detail View -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="card-title mb-0">Invoice <?php echo htmlspecialchars($viewInvoice['invoice_number']); ?></h5>
                <small class="text-muted">Date: <?php echo formatDate($viewInvoice['invoice_date']); ?></small>
            </div>
            <div>
                <span class="badge bg-<?php echo $viewInvoice['payment_status'] === 'Paid' ? 'success' : ($viewInvoice['payment_status'] === 'Partially Paid' ? 'warning' : 'danger'); ?>">
                    <?php echo ucfirst($viewInvoice['payment_status']); ?>
                </span>
                <a href="invoices.php" class="btn btn-sm btn-outline-secondary ms-2">Back</a>
            </div>
        </div>
        
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6><strong>Patient:</strong></h6>
                    <p><?php echo htmlspecialchars($viewInvoice['patient_name']); ?></p>
                    <h6><strong>Doctor:</strong></h6>
                    <p><?php echo htmlspecialchars($viewInvoice['doctor_name']); ?></p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h6><strong>Amount Due:</strong></h6>
                    <h3 class="text-danger">Rs. <?php echo number_format($viewInvoice['balance_due'], 2); ?></h3>
                </div>
            </div>
            
            <!-- Items Table -->
            <h6 class="mb-3"><strong>Invoice Items:</strong></h6>
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Item</th>
                            <th>Type</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($viewInvoice['items'] as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['item_description']); ?></td>
                                <td><span class="badge bg-secondary"><?php echo $item['item_type']; ?></span></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>Rs. <?php echo number_format($item['unit_price'], 2); ?></td>
                                <td><strong>Rs. <?php echo number_format($item['line_total'], 2); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Totals -->
            <div class="row mt-4">
                <div class="col-md-6 offset-md-6">
                    <div class="card bg-light">
                        <div class="card-body">
                            <div class="row mb-2">
                                <div class="col-6">Subtotal:</div>
                                <div class="col-6 text-end">Rs. <?php echo number_format($viewInvoice['subtotal'], 2); ?></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-6">Tax (<?php echo $viewInvoice['tax_percentage']; ?>%):</div>
                                <div class="col-6 text-end">Rs. <?php echo number_format($viewInvoice['tax_amount'], 2); ?></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-6">Discount:</div>
                                <div class="col-6 text-end">-Rs. <?php echo number_format($viewInvoice['discount_amount'], 2); ?></div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-6"><strong>Total:</strong></div>
                                <div class="col-6 text-end"><strong>Rs. <?php echo number_format($viewInvoice['total_amount'], 2); ?></strong></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Payment History -->
            <h6 class="mt-4 mb-3"><strong>Payment History:</strong></h6>
            <?php if (empty($viewInvoice['payments'])): ?>
                <p class="text-muted">No payments recorded yet.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Reference</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($viewInvoice['payments'] as $payment): ?>
                                <tr>
                                    <td><?php echo formatDate($payment['payment_date']); ?></td>
                                    <td><strong>Rs. <?php echo number_format($payment['amount_paid'], 2); ?></strong></td>
                                    <td><?php echo $payment['payment_method']; ?></td>
                                    <td><?php echo htmlspecialchars($payment['reference_number'] ?? '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <!-- Record Payment Form -->
            <?php if ($viewInvoice['payment_status'] !== 'Paid'): ?>
                <div class="card mt-4 border-info">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Record Payment</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="record_payment">
                            <input type="hidden" name="invoice_id" value="<?php echo $viewInvoice['invoice_id']; ?>">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Amount Paid (Rs.)</label>
                                    <input type="number" class="form-control" name="amount_paid" 
                                           max="<?php echo $viewInvoice['balance_due']; ?>" step="0.01" required>
                                    <small class="text-muted">Amount due: Rs. <?php echo number_format($viewInvoice['balance_due'], 2); ?></small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Payment Method</label>
                                    <select class="form-select" name="payment_method">
                                        <option value="Cash">Cash</option>
                                        <option value="Card">Card</option>
                                        <option value="Cheque">Cheque</option>
                                        <option value="Bank Transfer">Bank Transfer</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Payment Date</label>
                                    <input type="date" class="form-control" name="payment_date" value="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Reference Number</label>
                                    <input type="text" class="form-control" name="reference_number" placeholder="Cheque or Invoice #">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" name="notes" rows="2"></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-2"></i>Record Payment
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php else: ?>
    <!-- Invoices List -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">All Invoices</h5>
            <div>
                <input type="text" class="form-control form-control-sm" id="searchBox" 
                       placeholder="Search invoice..." style="width: 250px;">
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Invoice #</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Paid</th>
                            <th>Outstanding</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $allInvoices = $invoice->getDb()->fetchAll(
                            "SELECT i.*, CONCAT(p.first_name, ' ', p.last_name) as patient_name, u.full_name as doctor_name
                             FROM invoices i
                             JOIN patients p ON i.patient_id = p.patient_id
                             JOIN users u ON i.doctor_id = u.user_id
                             ORDER BY i.invoice_date DESC LIMIT 100"
                        );
                        
                        foreach ($allInvoices as $inv):
                        ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($inv['invoice_number']); ?></code></td>
                                <td><?php echo htmlspecialchars($inv['patient_name']); ?></td>
                                <td><?php echo htmlspecialchars($inv['doctor_name']); ?></td>
                                <td><?php echo formatDate($inv['invoice_date']); ?></td>
                                <td><strong>Rs. <?php echo number_format($inv['total_amount'], 2); ?></strong></td>
                                <td>Rs. <?php echo number_format($inv['paid_amount'], 2); ?></td>
                                <td class="text-danger"><strong>Rs. <?php echo number_format($inv['balance_due'], 2); ?></strong></td>
                                <td>
                                    <span class="badge bg-<?php echo $inv['payment_status'] === 'Paid' ? 'success' : ($inv['payment_status'] === 'Partially Paid' ? 'warning' : 'danger'); ?>">
                                        <?php echo ucfirst($inv['payment_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="invoices.php?id=<?php echo $inv['invoice_id']; ?>" 
                                       class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
// Search functionality
document.getElementById('searchBox')?.addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let table = document.querySelector('table tbody');
    if (!table) return;
    
    let rows = table.getElementsByTagName('tr');
    for (let row of rows) {
        let text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    }
});
</script>
