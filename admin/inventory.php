<?php
/**
 * Admin - Inventory Management
 * Manage medicine stock, reorders, expiry dates
 */

$pageTitle = 'Inventory Management';
$currentPage = 'inventory';

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../classes/Inventory.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$inventory = new Inventory();
$action = $_GET['action'] ?? '';
$medicineId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$errors = [];

// Handle stock adjustment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    checkCSRF();
    
    if ($_POST['action'] === 'adjust_stock') {
        $medicineId = (int)$_POST['medicine_id'];
        $quantity = (int)$_POST['quantity'];
        $transactionType = $_POST['transaction_type'];
        
        try {
            $inventory->updateStock($medicineId, $quantity, $transactionType, [
                'notes' => $_POST['notes'] ?? null,
                'recorded_by' => User::getUserId(),
                'transaction_date' => $_POST['transaction_date'] ?? date('Y-m-d')
            ]);
            
            setFlash('success', "Stock adjusted successfully!");
            redirect(APP_URL . '/admin/inventory.php?action=view&id=' . $medicineId);
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
}
?>

<div class="page-header">
    <h1><i class="bi bi-boxes me-2"></i>Inventory Management</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Inventory</li>
        </ol>
    </nav>
</div>

<?php displayFlash(); ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-circle me-2"></i>
        <?php foreach ($errors as $error): ?>
            <p><?php echo htmlspecialchars($error); ?></p>
        <?php endforeach; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Inventory Summary -->
<div class="row mb-4">
    <?php
    $summary = $inventory->getStockSummary();
    $valuation = $inventory->getInventoryValuation();
    ?>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-primary"><?php echo $summary['total_medicines'] ?? 0; ?></h3>
                <p class="text-muted">Total Medicines</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-warning"><?php echo $summary['low_stock_count'] ?? 0; ?></h3>
                <p class="text-muted">Low Stock</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-danger"><?php echo $summary['out_of_stock_count'] ?? 0; ?></h3>
                <p class="text-muted">Out of Stock</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-success">Rs. <?php echo number_format($valuation['total_retail_value'] ?? 0, 0); ?></h3>
                <p class="text-muted">Inventory Value</p>
            </div>
        </div>
    </div>
</div>

<?php if ($action === 'view' && $medicineId): ?>
    <!-- Stock Detail & Transaction History -->
    <?php $stock = $inventory->getStock($medicineId); ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><?php echo htmlspecialchars($stock['medicine_name']); ?></h5>
            <a href="inventory.php" class="btn btn-sm btn-outline-secondary float-end">Back to Inventory</a>
        </div>
        
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h2 class="text-primary"><?php echo $stock['current_quantity']; ?></h2>
                            <p class="mb-0">Current Stock</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h4><?php echo $stock['reorder_level']; ?></h4>
                            <p class="mb-0">Reorder Level</p>
                            <small class="text-muted">Reorder Qty: <?php echo $stock['reorder_quantity']; ?></small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h4>Rs. <?php echo number_format($stock['unit_cost'] ?? 0, 2); ?></h4>
                            <p class="mb-0">Unit Cost</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Adjust Stock Form -->
            <div class="card border-info mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Adjust Stock</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="adjust_stock">
                        <input type="hidden" name="medicine_id" value="<?php echo $medicineId; ?>">
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Transaction Type</label>
                                <select class="form-select" name="transaction_type" required>
                                    <option value="Adjustment">Adjustment</option>
                                    <option value="Sale">Sale</option>
                                    <option value="Return">Return</option>
                                    <option value="Damage">Damage</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Quantity</label>
                                <input type="number" class="form-control" name="quantity" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Date</label>
                                <input type="date" class="form-control" name="transaction_date" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="2"></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Adjust Stock
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Transaction History -->
            <h6 class="mb-3"><strong>Transaction History:</strong></h6>
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Quantity</th>
                            <th>Unit Cost</th>
                            <th>Notes</th>
                            <th>By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $history = $inventory->getStockHistory($medicineId);
                        foreach ($history as $transaction):
                        ?>
                            <tr>
                                <td><?php echo formatDate($transaction['transaction_date']); ?></td>
                                <td><span class="badge bg-secondary"><?php echo $transaction['transaction_type']; ?></span></td>
                                <td><?php echo $transaction['quantity']; ?></td>
                                <td>Rs. <?php echo number_format($transaction['unit_cost'] ?? 0, 2); ?></td>
                                <td><?php echo htmlspecialchars($transaction['notes'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($transaction['recorded_by_name'] ?? 'System'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- Inventory Tabs -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link active" href="#all" data-bs-toggle="tab">All Stock</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#lowstock" data-bs-toggle="tab">Low Stock</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#expiring" data-bs-toggle="tab">Expiring Soon</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#reorder" data-bs-toggle="tab">Reorder List</a>
        </li>
    </ul>
    
    <div class="tab-content">
        <!-- All Stock -->
        <div class="tab-pane fade show active" id="all">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Medicine</th>
                                    <th>Current Stock</th>
                                    <th>Reorder Level</th>
                                    <th>Unit Cost</th>
                                    <th>Last Updated</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $allStock = $inventory->getDb()->fetchAll(
                                    "SELECT ms.*, m.medicine_name FROM medicine_stock ms
                                     JOIN medicines m ON ms.medicine_id = m.medicine_id
                                     ORDER BY m.medicine_name"
                                );
                                
                                foreach ($allStock as $s):
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($s['medicine_name']); ?></td>
                                        <td>
                                            <strong><?php echo $s['current_quantity']; ?></strong>
                                            <?php if ($s['current_quantity'] <= $s['reorder_level']): ?>
                                                <span class="badge bg-danger">Low</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $s['reorder_level']; ?></td>
                                        <td>Rs. <?php echo number_format($s['unit_cost'] ?? 0, 2); ?></td>
                                        <td><?php echo formatDate($s['last_updated']); ?></td>
                                        <td>
                                            <a href="inventory.php?action=view&id=<?php echo $s['medicine_id']; ?>" 
                                               class="btn btn-sm btn-primary">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Low Stock -->
        <div class="tab-pane fade" id="lowstock">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Medicine</th>
                                    <th>Stock</th>
                                    <th>Reorder Level</th>
                                    <th>Shortage</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $lowStock = $inventory->getLowStockMedicines();
                                foreach ($lowStock as $med):
                                ?>
                                    <tr class="table-warning">
                                        <td><?php echo htmlspecialchars($med['medicine_name']); ?></td>
                                        <td><strong><?php echo $med['current_quantity']; ?></strong></td>
                                        <td><?php echo $med['reorder_level']; ?></td>
                                        <td><span class="badge bg-danger"><?php echo $med['shortage_qty']; ?></span></td>
                                        <td>
                                            <a href="inventory.php?action=view&id=<?php echo $med['medicine_id']; ?>" 
                                               class="btn btn-sm btn-warning">Reorder</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Expiring Soon -->
        <div class="tab-pane fade" id="expiring">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Medicine</th>
                                    <th>Batch Number</th>
                                    <th>Quantity</th>
                                    <th>Expiry Date</th>
                                    <th>Days Left</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $expiring = $inventory->getExpiringMedicines(30);
                                foreach ($expiring as $batch):
                                    $daysLeft = (strtotime($batch['expiry_date']) - time()) / 86400;
                                ?>
                                    <tr class="table-<?php echo $daysLeft < 7 ? 'danger' : 'warning'; ?>">
                                        <td><?php echo htmlspecialchars($batch['medicine_name']); ?></td>
                                        <td><code><?php echo htmlspecialchars($batch['batch_number']); ?></code></td>
                                        <td><?php echo $batch['quantity']; ?></td>
                                        <td><?php echo formatDate($batch['expiry_date']); ?></td>
                                        <td><span class="badge bg-danger"><?php echo round($daysLeft); ?> days</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Reorder List -->
        <div class="tab-pane fade" id="reorder">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Medicine</th>
                                    <th>Current Stock</th>
                                    <th>Reorder Quantity</th>
                                    <th>Estimated Cost</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $reorderList = $inventory->getReorderList();
                                foreach ($reorderList as $item):
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['medicine_name']); ?></td>
                                        <td><?php echo $item['current_quantity']; ?></td>
                                        <td><strong><?php echo $item['order_qty']; ?></strong></td>
                                        <td>Rs. <?php echo number_format($item['order_cost'] ?? 0, 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
