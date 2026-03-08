<?php
/**
 * Amend Prescription
 * Create a new amended prescription based on an existing one
 */

require_once __DIR__ . '/../includes/auth.php';
requireDoctor();
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';   // ADD THIS 
require_once __DIR__ . '/../classes/Prescription.php';
require_once __DIR__ . '/../classes/Visit.php';

$prescriptionId = isset($_GET['prescription_id']) ? (int)$_GET['prescription_id'] : 0;
$visitId = isset($_GET['visit_id']) ? (int)$_GET['visit_id'] : 0;

if (!$prescriptionId || !$visitId) {
    redirect(APP_URL . '/doctor/index.php', 'danger', 'Invalid prescription or visit.');
}

$prescriptionObj = new Prescription();
$visitObj = new Visit();

$originalRx = $prescriptionObj->getById($prescriptionId);
$visit = $visitObj->getById($visitId);

if (!$originalRx || !$visit) {
    redirect(APP_URL . '/doctor/index.php', 'danger', 'Prescription or visit not found.');
}

// Check authorization
if ($visit['doctor_id'] != User::getUserId()) {
    redirect(APP_URL . '/doctor/index.php', 'danger', 'Unauthorized access.');
}

$medicines = $prescriptionObj->getMedicines($prescriptionId);
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCSRF();
    
    try {
        Database::getInstance()->beginTransaction();
        
        // Create new amended prescription
        $amendedData = [
            'prescription_code' => $prescriptionObj->generatePrescriptionCode(),
            'visit_id' => $visitId,
            'patient_id' => $originalRx['patient_id'],
            'doctor_id' => User::getUserId(),
            'prescription_date' => date('Y-m-d'),
            'notes' => sanitize($_POST['prescription_notes'] ?? ''),
            'parent_prescription_id' => $prescriptionId // Link to original
        ];
        
        // Insert amendment - we need to modify the Prescription class
        $sql = "INSERT INTO prescriptions (
                    prescription_code, visit_id, patient_id, doctor_id,
                    prescription_date, notes, parent_prescription_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $db = Database::getInstance();
        $amendedRxId = $db->insert($sql, [
            $amendedData['prescription_code'],
            $amendedData['visit_id'],
            $amendedData['patient_id'],
            $amendedData['doctor_id'],
            $amendedData['prescription_date'],
            $amendedData['notes'],
            $prescriptionId
        ]);
        
        if (!$amendedRxId) {
            throw new Exception('Failed to create amended prescription.');
        }
        
        // Add medicines
        if (!empty($_POST['medicines'])) {
            $medicinesList = [];
            foreach ($_POST['medicines'] as $med) {
                if (!empty($med['medicine_name'])) {
                    $medicinesList[] = [
                        'medicine_name' => sanitize($med['medicine_name']),
                        'dose' => sanitize($med['dose'] ?? ''),
                        'frequency' => sanitize($med['frequency'] ?? ''),
                        'duration_days' => (int)($med['duration_days'] ?? 0),
                        'route' => $med['route'] ?? 'Oral',
                        'instructions' => sanitize($med['instructions'] ?? '')
                    ];
                }
            }
            
            if (!empty($medicinesList)) {
                $prescriptionObj->addMedicines($amendedRxId, $medicinesList);
            }
        }
        
        Database::getInstance()->commit();
        
        // Log activity
        $user = new User();
        $user->logActivity('Amend Prescription', 'prescriptions', $amendedRxId, 
                          'Created amended prescription for original RX #' . $prescriptionId);
        
        redirect(APP_URL . '/doctor/print-prescription.php?id=' . $amendedRxId, 'success', 
                'Prescription amended successfully! Printing...');
        
    } catch (Exception $e) {
        Database::getInstance()->rollback();
        $errors[] = 'Error: ' . $e->getMessage();
    }
}

$pageTitle = 'Amend Prescription';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h1><i class="bi bi-pencil-square me-2"></i>Amend Prescription</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="visit-prescriptions.php?visit_id=<?php echo $visitId; ?>">Prescriptions</a></li>
            <li class="breadcrumb-item active">Amend</li>
        </ol>
    </nav>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-circle me-2"></i>
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Original Prescription Info -->
<div class="card mb-4 bg-secondary text-white">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6 class="card-title">Original Prescription</h6>
                <p class="mb-1"><strong><?php echo $originalRx['prescription_code']; ?></strong></p>
                <small class="text-white-50">
                    Created: <?php echo formatDateTime($originalRx['created_at']); ?>
                </small>
            </div>
            <div class="col-md-6">
                <h6 class="card-title">Patient & Visit</h6>
                <p class="mb-1"><strong><?php echo htmlspecialchars($originalRx['patient_name']); ?></strong></p>
                <small class="text-white-50">
                    Visit: <?php echo formatDate($visit['visit_date']); ?>
                </small>
            </div>
        </div>
    </div>
</div>

<form method="POST" action="" id="amendForm">
    <?php echo csrfField(); ?>
    
    <!-- Original Medicines -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="bi bi-clipboard-check me-2"></i>Original Medicines</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Medicine</th>
                            <th>Dose</th>
                            <th>Frequency</th>
                            <th>Duration</th>
                            <th>Route</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($medicines as $med): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($med['medicine_name']); ?></td>
                                <td><?php echo htmlspecialchars($med['dose']); ?></td>
                                <td><?php echo htmlspecialchars($med['frequency']); ?></td>
                                <td><?php echo $med['duration_days']; ?> days</td>
                                <td><span class="badge bg-secondary"><?php echo $med['route']; ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- New Medicines for Amendment -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-capsule me-2"></i>Amended Medicines</h5>
            <button type="button" class="btn btn-sm btn-outline-primary" id="addMedicineBtn">
                <i class="bi bi-plus"></i> Add Medicine
            </button>
        </div>
        <div class="card-body" id="medicinesContainer">
            <div class="medicine-row rounded p-2 mb-2" style="background-color: #f8f9fa; border: 2px solid #dee2e6;">
                <div class="row g-2">
                    <div class="col-md-6">
                        <select class="form-select form-select-sm medicine-select" name="medicines[0][medicine_name]">
                            <option value="">Select Medicine</option>
                            <?php foreach (getMedicines() as $medicine): ?>
                                <option value="<?php echo htmlspecialchars($medicine['medicine_name']); ?>"><?php echo htmlspecialchars($medicine['medicine_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="form-control form-control-sm" name="medicines[0][dose]" 
                               placeholder="Dose (e.g., 500mg)">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select form-select-sm" name="medicines[0][route]">
                            <?php foreach (getMedicineRoutes() as $route): ?>
                                <option value="<?php echo $route; ?>"><?php echo $route; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="row g-2 mt-1">
                    <div class="col-md-4">
                        <select class="form-select form-select-sm" name="medicines[0][frequency]">
                            <option value="">Frequency</option>
                            <?php foreach (getFrequencies() as $freq): ?>
                                <option value="<?php echo $freq; ?>"><?php echo $freq; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="number" class="form-control form-control-sm" name="medicines[0][duration_days]" 
                               placeholder="Days" min="1" max="365">
                    </div>
                    <div class="col-md-4">
                        <input type="text" class="form-control form-control-sm" name="medicines[0][instructions]" 
                               placeholder="Instructions">
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-medicine" title="Remove">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <label class="form-label">Amendment Notes</label>
            <textarea class="form-control form-control-sm" name="prescription_notes" rows="3" 
                      placeholder="What was changed and why..."></textarea>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="card">
        <div class="card-body d-flex gap-2">
            <button type="submit" class="btn btn-success btn-lg">
                <i class="bi bi-check-circle me-2"></i>Create Amendment & Print
            </button>
            <a href="visit-prescriptions.php?visit_id=<?php echo $visitId; ?>" class="btn btn-secondary btn-lg">
                <i class="bi bi-x-circle me-2"></i>Cancel
            </a>
        </div>
    </div>
</form>

<!-- Select2 & Scripts -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    $('.medicine-select').select2({
        theme: 'bootstrap-5',
        width: '100%',
        allowClear: true,
        placeholder: 'Search and select medicine...'
    });
});

document.addEventListener('DOMContentLoaded', function() {
    let medicineIndex = 1;
    
    const medicineColors = [
        '#e8f4f8', '#f0f8e8', '#fff8e8', '#ffe8f0', 
        '#f0e8f8', '#f8e8e8', '#e8f8f0'
    ];
    
    document.getElementById('addMedicineBtn').addEventListener('click', function() {
        const container = document.getElementById('medicinesContainer');
        const medicineCount = document.querySelectorAll('.medicine-row').length;
        const bgColor = medicineColors[medicineCount % medicineColors.length];
        
        const html = `
            <div class="medicine-row rounded p-2 mb-2" style="background-color: ${bgColor}; border: 2px solid #dee2e6;">
                <div class="row g-2">
                    <div class="col-md-6">
                        <select class="form-select form-select-sm medicine-select" name="medicines[${medicineIndex}][medicine_name]">
                            <option value="">Select Medicine</option>
                            <?php foreach (getMedicines() as $medicine): ?>
                                <option value="<?php echo htmlspecialchars($medicine['medicine_name']); ?>"><?php echo htmlspecialchars($medicine['medicine_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="form-control form-control-sm" name="medicines[${medicineIndex}][dose]" 
                               placeholder="Dose">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select form-select-sm" name="medicines[${medicineIndex}][route]">
                            <?php foreach (getMedicineRoutes() as $route): ?>
                                <option value="<?php echo $route; ?>"><?php echo $route; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="row g-2 mt-1">
                    <div class="col-md-4">
                        <select class="form-select form-select-sm" name="medicines[${medicineIndex}][frequency]">
                            <option value="">Frequency</option>
                            <?php foreach (getFrequencies() as $freq): ?>
                                <option value="<?php echo $freq; ?>"><?php echo $freq; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="number" class="form-control form-control-sm" name="medicines[${medicineIndex}][duration_days]" 
                               placeholder="Days" min="1" max="365">
                    </div>
                    <div class="col-md-4">
                        <input type="text" class="form-control form-control-sm" name="medicines[${medicineIndex}][instructions]" 
                               placeholder="Instructions">
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-medicine" title="Remove">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        const newRow = document.createElement('div');
        newRow.innerHTML = html;
        container.insertBefore(newRow.firstElementChild, container.firstChild);
        
        $(newRow.firstElementChild.querySelector('.medicine-select')).select2({
            theme: 'bootstrap-5',
            width: '100%',
            allowClear: true,
            placeholder: 'Search and select medicine...'
        });
        
        medicineIndex++;
    });
    
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-medicine')) {
            const row = e.target.closest('.medicine-row');
            if (document.querySelectorAll('.medicine-row').length > 1) {
                row.remove();
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
