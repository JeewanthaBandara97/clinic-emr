<?php
/**
 * View Visit Prescriptions & Amendments
 * Allows doctors to view and amend prescriptions for completed visits
 */

require_once __DIR__ . '/../includes/auth.php';
requireDoctor();
require_once __DIR__ . '/../classes/Visit.php';
require_once __DIR__ . '/../classes/Prescription.php';

$visitId = isset($_GET['visit_id']) ? (int)$_GET['visit_id'] : 0;

if (!$visitId) {
    redirect(APP_URL . '/doctor/index.php', 'danger', 'No visit selected.');
}

$visitObj = new Visit();
$prescriptionObj = new Prescription();

$visit = $visitObj->getById($visitId);

if (!$visit) {
    redirect(APP_URL . '/doctor/index.php', 'danger', 'Visit not found.');
}

// Only allow doctor to view own visits
if ($visit['doctor_id'] != User::getUserId()) {
    redirect(APP_URL . '/doctor/index.php', 'danger', 'Unauthorized access.');
}

// Get all prescriptions for this visit (original + amendments)
$sql = "SELECT pr.*, u.full_name as doctor_name,
           (SELECT COUNT(*) FROM patient_prescriptions WHERE parent_prescription_id = pr.prescription_id) as amendment_count
        FROM patient_prescriptions pr
        JOIN users u ON pr.doctor_id = u.user_id
        WHERE pr.visit_id = ?
        ORDER BY pr.prescription_id ASC";

$db = Database::getInstance();
$prescriptions = $db->fetchAll($sql, [$visitId]);

if (empty($prescriptions)) {
    $prescriptions = [];
}

$pageTitle = 'Visit Prescriptions - ' . $visit['patient_name'];
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h1><i class="bi bi-capsule me-2"></i>Prescriptions for Visit</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="patient-history.php?id=<?php echo $visit['patient_id']; ?>">Patient History</a></li>
            <li class="breadcrumb-item active">Prescriptions</li>
        </ol>
    </nav>
</div>

<!-- Patient & Visit Info -->
<div class="card mb-4 bg-info text-white">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h5 class="card-title mb-0"><?php echo htmlspecialchars($visit['patient_name']); ?></h5>
                <small class="text-white-50">
                    <i class="bi bi-upc me-1"></i><?php echo $visit['patient_code']; ?> |
                    <i class="bi bi-calendar3 me-1"></i><?php echo formatDate($visit['visit_date']); ?> |
                    <i class="bi bi-hourglass-split me-1"></i><?php echo formatTime($visit['visit_time']); ?>
                </small>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="patient-history.php?id=<?php echo $visit['patient_id']; ?>" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Back to History
                </a>
            </div>
        </div>
    </div>
</div>

<?php if (empty($prescriptions)): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-circle me-2"></i>
        No prescriptions found for this visit.
    </div>
<?php else: ?>
    <?php foreach ($prescriptions as $idx => $rx): ?>
        <div class="card mb-4 <?php echo $rx['parent_prescription_id'] ? 'border-warning' : ''; ?>">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-0">
                        <?php if ($rx['parent_prescription_id']): ?>
                            <span class="badge bg-warning text-dark me-2">
                                <i class="bi bi-arrow-repeat me-1"></i>AMENDED
                            </span>
                        <?php else: ?>
                            <span class="badge bg-success me-2">
                                <i class="bi bi-check-circle me-1"></i>ORIGINAL
                            </span>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($rx['prescription_code']); ?>
                    </h5>
                    <small class="text-muted">
                        Created: <?php echo formatDateTime($rx['created_at']); ?> | 
                        Status: <strong><?php echo $rx['status']; ?></strong>
                        <?php if ($rx['amendment_count'] > 0): ?>
                            | <strong><?php echo $rx['amendment_count']; ?> Amendment(s)</strong>
                        <?php endif; ?>
                    </small>
                </div>
                <div>
                    <a href="print-prescription.php?id=<?php echo $rx['prescription_id']; ?>" 
                       class="btn btn-sm btn-primary" target="_blank">
                        <i class="bi bi-printer me-1"></i>Print
                    </a>
                    <?php if ($rx['status'] === 'Active'): ?>
                        <a href="amend-prescription.php?prescription_id=<?php echo $rx['prescription_id']; ?>&visit_id=<?php echo $visitId; ?>" 
                           class="btn btn-sm btn-warning">
                            <i class="bi bi-pencil me-1"></i>Amend
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card-body">
                <?php if ($rx['notes']): ?>
                    <div class="mb-3 p-2 bg-light rounded">
                        <strong>Notes:</strong> <?php echo htmlspecialchars($rx['notes']); ?>
                    </div>
                <?php endif; ?>
                
                <h6 class="mb-2"><i class="bi bi-capsule me-1"></i>Medicines:</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Medicine</th>
                                <th>Dose</th>
                                <th>Frequency</th>
                                <th>Duration</th>
                                <th>Route</th>
                                <th>Instructions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $medicines = $prescriptionObj->getMedicines($rx['prescription_id']);
                            if (empty($medicines)):
                            ?>
                                <tr><td colspan="6" class="text-center text-muted">No medicines in this prescription</td></tr>
                            <?php else:
                                foreach ($medicines as $med):
                            ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($med['medicine_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($med['dose']); ?></td>
                                    <td><?php echo htmlspecialchars($med['frequency']); ?></td>
                                    <td><?php echo $med['duration_days']; ?> days</td>
                                    <td><span class="badge bg-secondary"><?php echo $med['route']; ?></span></td>
                                    <td><small><?php echo htmlspecialchars($med['instructions'] ?? '-'); ?></small></td>
                                </tr>
                            <?php
                                endforeach;
                            endif;
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
