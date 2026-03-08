<?php
/**
 * Medicine Management - List All Medicines
 */

$pageTitle = 'Medicine Management';

require_once __DIR__ . '/../includes/auth.php';
requireAssistant();
require_once __DIR__ . '/../includes/functions.php';   // ADD THIS
require_once __DIR__ . '/../includes/csrf.php';   // ADD THIS
require_once __DIR__ . '/../classes/Medicine.php';

$medicine = new Medicine();

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    checkCSRF();
    
    if ($_POST['action'] === 'delete') {
        $medicineId = (int)($_POST['medicine_id'] ?? 0);
        if ($medicineId > 0 && $medicine->delete($medicineId)) {
            redirect(APP_URL . '/assistant/medicines.php', 'success', 'Medicine deleted successfully.');
        } else {
            redirect(APP_URL . '/assistant/medicines.php', 'danger', 'Failed to delete medicine.');
        }
    }
}

// Search & Pagination
$search = trim($_GET['search'] ?? '');
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 15;

if (!empty($search)) {
    $medicines = $medicine->search($search, 100);
    $totalMedicines = count($medicines);
    $pagination = null;
} else {
    $totalMedicines = $medicine->getCount();
    $pagination = getPagination($totalMedicines, $page, $perPage);
    $medicines = $medicine->getAll($pagination['offset'], $pagination['per_page']);
}

// Get stats
$stats = $medicine->getStats();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header d-flex justify-content-between align-items-center flex-wrap">
    <div>
        <h1><i class="bi bi-capsule me-2"></i>Medicine Management</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Medicines</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="medicine-lookups.php" class="btn btn-outline-secondary">
            <i class="bi bi-gear me-1"></i>Manage Lookups
        </a>
        <a href="add-medicine.php" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i>Add Medicine
        </a>
    </div>
</div>

<?php displayFlash(); ?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-6 col-md-2 mb-2">
        <div class="card bg-primary text-white h-100">
            <div class="card-body text-center py-3">
                <h3 class="mb-0"><?php echo $stats['total_medicines'] ?? 0; ?></h3>
                <small>Medicines</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2 mb-2">
        <div class="card bg-success text-white h-100">
            <div class="card-body text-center py-3">
                <h3 class="mb-0"><?php echo $stats['main_categories'] ?? 0; ?></h3>
                <small>Categories</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2 mb-2">
        <div class="card bg-info text-white h-100">
            <div class="card-body text-center py-3">
                <h3 class="mb-0"><?php echo $stats['generic_names'] ?? 0; ?></h3>
                <small>Generic Names</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2 mb-2">
        <div class="card bg-warning h-100">
            <div class="card-body text-center py-3">
                <h3 class="mb-0"><?php echo $stats['trade_names'] ?? 0; ?></h3>
                <small>Trade Names</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2 mb-2">
        <div class="card bg-secondary text-white h-100">
            <div class="card-body text-center py-3">
                <h3 class="mb-0"><?php echo $stats['issuing_units'] ?? 0; ?></h3>
                <small>Units</small>
            </div>
        </div>
    </div>
</div>

<!-- Search -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-10">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control form-control-lg" name="search" 
                           placeholder="Search by name, code, generic name, or trade name..."
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-2">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-lg flex-grow-1">
                        <i class="bi bi-search"></i>
                    </button>
                    <?php if ($search): ?>
                        <a href="medicines.php" class="btn btn-outline-secondary btn-lg">
                            <i class="bi bi-x"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Medicines Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-list me-2"></i>
            <?php echo $search ? 'Search Results' : 'All Medicines'; ?>
            <span class="badge bg-primary ms-1"><?php echo $totalMedicines; ?></span>
        </span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($medicines)): ?>
            <div class="text-center py-5">
                <i class="bi bi-capsule display-1 text-muted"></i>
                <h4 class="text-muted mt-3">No Medicines Found</h4>
                <p class="text-muted">
                    <?php echo $search ? 'No medicines match your search criteria.' : 'Start by adding your first medicine.'; ?>
                </p>
                <a href="add-medicine.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Add Medicine
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Medicine Name</th>
                            <th>Generic Name</th>
                            <th>Strength</th>
                            <th>Category</th>
                            <th>Unit</th>
                            <th>MRP</th>
                            <th width="120">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($medicines as $med): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($med['medicine_code']); ?></code></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($med['medicine_name']); ?></strong>
                                    <?php if ($med['trade_name']): ?>
                                        <br><small class="text-muted">
                                            <i class="bi bi-tag me-1"></i><?php echo htmlspecialchars($med['trade_name']); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($med['generic_name']): ?>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($med['generic_name']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($med['strength'] ?? '-'); ?></td>
                                <td>
                                    <?php if ($med['main_category']): ?>
                                        <small><?php echo htmlspecialchars($med['main_category']); ?></small>
                                        <?php if ($med['sub_category_1']): ?>
                                            <br><small class="text-muted">└ <?php echo htmlspecialchars($med['sub_category_1']); ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($med['issuing_unit']): ?>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($med['issuing_unit']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($med['mrp'] > 0): ?>
                                        <strong>Rs. <?php echo number_format($med['mrp'], 2); ?></strong>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="edit-medicine.php?id=<?php echo $med['medicine_id']; ?>" 
                                           class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-danger" 
                                                onclick="confirmDelete(<?php echo $med['medicine_id']; ?>, '<?php echo htmlspecialchars(addslashes($med['medicine_name'])); ?>')"
                                                title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($pagination && $pagination['total_pages'] > 1): ?>
                <div class="card-footer">
                    <?php echo renderPagination($pagination, 'medicines.php'); ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-trash me-2"></i>Delete Medicine</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <i class="bi bi-exclamation-triangle text-warning display-4"></i>
                <p class="mt-3">Are you sure you want to delete:<br><strong id="deleteMedicineName"></strong>?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" class="d-inline">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="medicine_id" id="deleteMedicineId">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i>Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, name) {
    document.getElementById('deleteMedicineId').value = id;
    document.getElementById('deleteMedicineName').textContent = name;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>