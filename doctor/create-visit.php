<?php
/**
 * Create Patient Visit
 * Clinic EMR System
 */

require_once __DIR__ . '/../includes/auth.php';
requireDoctor();
require_once __DIR__ . '/../includes/functions.php';   // ADD THIS
require_once __DIR__ . '/../includes/csrf.php';   // ADD THIS
require_once __DIR__ . '/../classes/Patient.php';
require_once __DIR__ . '/../classes/Session.php';
require_once __DIR__ . '/../classes/Visit.php';
require_once __DIR__ . '/../classes/Test.php';
require_once __DIR__ . '/../classes/Prescription.php';

$patientId = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
$sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : null;

if (!$patientId) {
    redirect(APP_URL . '/doctor/patient-queue.php', 'danger', 'Please select a patient.');
}

$patientObj = new Patient();
$sessionObj = new ClinicSession();
$visitObj = new Visit();
$testObj = new Test();
$prescriptionObj = new Prescription();

$patient = $patientObj->getById($patientId);

if (!$patient) {
    redirect(APP_URL . '/doctor/patient-queue.php', 'danger', 'Patient not found.');
}

$doctorId = User::getUserId();
$errors = [];
$visitId = null;

// Update queue status if session provided
if ($sessionId) {
    $sessionObj->updatePatientStatus($sessionId, $patientId, 'In Progress');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCSRF();
    
    $action = $_POST['action'] ?? '';
    
    try {
        $visitObj->getConnection = Database::getInstance();
        Database::getInstance()->beginTransaction();
        
        // Create or update visit
        $visitData = [
            'visit_code' => $visitObj->generateVisitCode(),
            'patient_id' => $patientId,
            'doctor_id' => $doctorId,
            'session_id' => $sessionId,
            'visit_date' => date('Y-m-d'),
            'visit_time' => date('H:i:s'),
            'symptoms' => sanitize($_POST['symptoms'] ?? ''),
            'diagnosis' => sanitize($_POST['diagnosis'] ?? ''),
            'notes' => sanitize($_POST['notes'] ?? ''),
            'follow_up_date' => !empty($_POST['follow_up_date']) ? $_POST['follow_up_date'] : null,
            'status' => $action === 'complete' ? 'Completed' : 'In Progress'
        ];
        
        $visitId = $visitObj->create($visitData);
        
        if (!$visitId) {
            throw new Exception('Failed to create visit record.');
        }
        
        // Save vital signs
        $vitalData = [
            'visit_id' => $visitId,
            'patient_id' => $patientId,
            'temperature' => !empty($_POST['temperature']) ? (float)$_POST['temperature'] : null,
            'blood_pressure_systolic' => !empty($_POST['bp_systolic']) ? (int)$_POST['bp_systolic'] : null,
            'blood_pressure_diastolic' => !empty($_POST['bp_diastolic']) ? (int)$_POST['bp_diastolic'] : null,
            'pulse_rate' => !empty($_POST['pulse_rate']) ? (int)$_POST['pulse_rate'] : null,
            'respiratory_rate' => !empty($_POST['respiratory_rate']) ? (int)$_POST['respiratory_rate'] : null,
            'weight' => !empty($_POST['weight']) ? (float)$_POST['weight'] : null,
            'height' => !empty($_POST['height']) ? (float)$_POST['height'] : null,
            'oxygen_saturation' => !empty($_POST['oxygen_saturation']) ? (int)$_POST['oxygen_saturation'] : null,
            'bmi' => null,
            'notes' => null
        ];
        
        // Calculate BMI if weight and height provided
        if ($vitalData['weight'] && $vitalData['height']) {
            $vitalData['bmi'] = calculateBMI($vitalData['weight'], $vitalData['height']);
        }
        
        $visitObj->saveVitalSigns($vitalData);
        
        // Save tests
        if (!empty($_POST['tests'])) {
            $tests = [];
            foreach ($_POST['tests'] as $test) {
                if (!empty($test['test_type'])) {
                    $tests[] = [
                        'test_type' => $test['test_type'],
                        'test_name' => $test['test_name'] ?? $test['test_type'],
                        'instructions' => $test['instructions'] ?? null,
                        'urgency' => $test['urgency'] ?? 'Routine'
                    ];
                }
            }
            if (!empty($tests)) {
                $testObj->createMultiple($visitId, $patientId, $tests);
            }
        }
        
        // Save prescription
        if (!empty($_POST['medicines'])) {
            $hasMedicines = false;
            foreach ($_POST['medicines'] as $med) {
                if (!empty($med['medicine_name'])) {
                    $hasMedicines = true;
                    break;
                }
            }
            
            if ($hasMedicines) {
                $prescriptionData = [
                    'prescription_code' => $prescriptionObj->generatePrescriptionCode(),
                    'visit_id' => $visitId,
                    'patient_id' => $patientId,
                    'doctor_id' => $doctorId,
                    'prescription_date' => date('Y-m-d'),
                    'notes' => sanitize($_POST['prescription_notes'] ?? '')
                ];
                
                $prescriptionId = $prescriptionObj->create($prescriptionData);
                
                if ($prescriptionId) {
                    $medicines = [];
                    foreach ($_POST['medicines'] as $med) {
                        if (!empty($med['medicine_name'])) {
                            $medicines[] = [
                                'medicine_name' => sanitize($med['medicine_name']),
                                'dose' => sanitize($med['dose'] ?? ''),
                                'frequency' => sanitize($med['frequency'] ?? ''),
                                'duration_days' => (int)($med['duration_days'] ?? 0),
                                'route' => $med['route'] ?? 'Oral',
                                'instructions' => sanitize($med['instructions'] ?? '')
                            ];
                        }
                    }
                    $prescriptionObj->addMedicines($prescriptionId, $medicines);
                }
            }
        }
        
        // Update queue status if completing
        if ($action === 'complete' && $sessionId) {
            $sessionObj->updatePatientStatus($sessionId, $patientId, 'Completed');
        }
        
        Database::getInstance()->commit();
        
        // Log activity
        $user = new User();
        $user->logActivity('Create Visit', 'visits', $visitId, 'Created visit for patient ' . $patientId);
        
        if ($action === 'complete') {
            // Redirect to print prescription if exists
            if (isset($prescriptionId)) {
                redirect(APP_URL . '/doctor/print-prescription.php?id=' . $prescriptionId, 'success', 'Visit completed successfully!');
            } else {
                redirect(APP_URL . '/doctor/patient-queue.php', 'success', 'Visit completed successfully!');
            }
        } else {
            setFlash('success', 'Visit saved successfully!');
        }
        
    } catch (Exception $e) {
        Database::getInstance()->rollback();
        $errors[] = 'An error occurred: ' . $e->getMessage();
    }
}

$pageTitle = 'Visit - ' . $patient['full_name'];

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h1><i class="bi bi-clipboard-plus me-2"></i>Patient Visit</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="patient-queue.php">Queue</a></li>
            <li class="breadcrumb-item active">New Visit</li>
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

<?php displayFlash(); ?>

<!-- Patient Info Banner -->
<div class="card mb-4 bg-primary text-white">
    <div class="card-body py-3">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h4 class="mb-1"><?php echo htmlspecialchars($patient['full_name']); ?></h4>
                <div>
                    <span class="me-3"><i class="bi bi-upc me-1"></i><?php echo $patient['patient_code']; ?></span>
                    <span class="me-3"><i class="bi bi-calendar3 me-1"></i><?php echo $patient['age']; ?>y</span>
                    <span class="me-3"><i class="bi bi-droplet me-1"></i><?php echo $patient['blood_group']; ?></span>
                    <span><i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($patient['phone']); ?></span>
                </div>
                <?php if ($patient['allergies']): ?>
                    <div class="mt-2">
                        <span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i>Allergies: <?php echo htmlspecialchars($patient['allergies']); ?></span>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="patient-history.php?id=<?php echo $patientId; ?>" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-clock-history me-1"></i>View History
                </a>
            </div>
        </div>
    </div>
</div>

<form method="POST" action="" id="visitForm">
    <?php echo csrfField(); ?>
    
    <div class="row">
        <!-- Left Column: Vital Signs & Symptoms -->
        <div class="col-lg-6 mb-4">
            <!-- Vital Signs -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-activity me-2"></i>Vital Signs
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Temperature (°C)</label>
                            <input type="number" step="0.1" class="form-control" name="temperature" 
                                   placeholder="36.5" min="30" max="45">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">BP Systolic</label>
                            <input type="number" class="form-control" name="bp_systolic" 
                                   placeholder="120" min="60" max="250">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">BP Diastolic</label>
                            <input type="number" class="form-control" name="bp_diastolic" 
                                   placeholder="80" min="40" max="150">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Pulse Rate</label>
                            <input type="number" class="form-control" name="pulse_rate" 
                                   placeholder="72" min="30" max="200">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Weight (kg)</label>
                            <input type="number" step="0.1" class="form-control" name="weight" id="weight"
                                   placeholder="70" min="1" max="500"
                                   value="<?php echo $patient['weight'] ?? ''; ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Height (cm)</label>
                            <input type="number" step="0.1" class="form-control" name="height" id="height"
                                   placeholder="170" min="30" max="250"
                                   value="<?php echo $patient['height'] ?? ''; ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">SpO2 (%)</label>
                            <input type="number" class="form-control" name="oxygen_saturation" 
                                   placeholder="98" min="50" max="100">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Respiratory Rate</label>
                            <input type="number" class="form-control" name="respiratory_rate" 
                                   placeholder="16" min="5" max="60">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Symptoms & Diagnosis -->
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-clipboard2-pulse me-2"></i>Symptoms & Diagnosis
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Symptoms / Chief Complaint</label>
                        <textarea class="form-control" name="symptoms" rows="3" 
                                  placeholder="Describe patient's symptoms..."><?php echo htmlspecialchars($_POST['symptoms'] ?? ''); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Diagnosis</label>
                        <textarea class="form-control" name="diagnosis" rows="3" 
                                  placeholder="Enter diagnosis..."><?php echo htmlspecialchars($_POST['diagnosis'] ?? ''); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="2" 
                                  placeholder="Additional notes..."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                    </div>
                    <div>
                        <label class="form-label">Follow-up Date</label>
                        <input type="date" class="form-control" name="follow_up_date" 
                               min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Column: Tests & Prescription -->
        <div class="col-lg-6 mb-4">
            <!-- Test Requests -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-clipboard-check me-2"></i>Test Requests</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addTestBtn">
                        <i class="bi bi-plus"></i> Add Test
                    </button>
                </div>
                <div class="card-body" id="testsContainer">
                    <div class="test-row row g-2 mb-2">
                        <div class="col-md-4">
                            <select class="form-select form-select-sm" name="tests[0][test_type]">
                                <option value="">Select Test</option>
                                <?php foreach (getTestTypes() as $type): ?>
                                    <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="text" class="form-control form-control-sm" name="tests[0][test_name]" placeholder="Test name">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" name="tests[0][urgency]">
                                <option value="Routine">Routine</option>
                                <option value="Urgent">Urgent</option>
                                <option value="STAT">STAT</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-test" title="Remove">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Prescription -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-capsule me-2"></i>Prescription</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addMedicineBtn">
                        <i class="bi bi-plus"></i> Add Medicine
                    </button>
                </div>
                <div class="card-body" id="medicinesContainer">
                    <div class="medicine-row border rounded p-2 mb-2">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <input type="text" class="form-control form-control-sm" name="medicines[0][medicine_name]" 
                                       placeholder="Medicine name">
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
                    <textarea class="form-control form-control-sm" name="prescription_notes" rows="2" 
                              placeholder="Prescription notes (advice, warnings, etc.)"></textarea>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="card">
        <div class="card-body d-flex gap-2">
            <button type="submit" name="action" value="save" class="btn btn-primary btn-lg">
                <i class="bi bi-save me-2"></i>Save Visit
            </button>
            <button type="submit" name="action" value="complete" class="btn btn-success btn-lg">
                <i class="bi bi-check-circle me-2"></i>Complete & Print
            </button>
            <a href="patient-queue.php" class="btn btn-secondary btn-lg">
                <i class="bi bi-x-circle me-2"></i>Cancel
            </a>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let testIndex = 1;
    let medicineIndex = 1;
    
    // Add Test Row
    document.getElementById('addTestBtn').addEventListener('click', function() {
        const container = document.getElementById('testsContainer');
        const html = `
            <div class="test-row row g-2 mb-2">
                <div class="col-md-4">
                    <select class="form-select form-select-sm" name="tests[${testIndex}][test_type]">
                        <option value="">Select Test</option>
                        <?php foreach (getTestTypes() as $type): ?>
                            <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" class="form-control form-control-sm" name="tests[${testIndex}][test_name]" placeholder="Test name">
                </div>
                <div class="col-md-3">
                    <select class="form-select form-select-sm" name="tests[${testIndex}][urgency]">
                        <option value="Routine">Routine</option>
                        <option value="Urgent">Urgent</option>
                        <option value="STAT">STAT</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-test" title="Remove">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
        testIndex++;
    });
    
    // Add Medicine Row
    document.getElementById('addMedicineBtn').addEventListener('click', function() {
        const container = document.getElementById('medicinesContainer');
        const html = `
            <div class="medicine-row border rounded p-2 mb-2">
                <div class="row g-2">
                    <div class="col-md-6">
                        <input type="text" class="form-control form-control-sm" name="medicines[${medicineIndex}][medicine_name]" 
                               placeholder="Medicine name">
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="form-control form-control-sm" name="medicines[${medicineIndex}][dose]" 
                               placeholder="Dose (e.g., 500mg)">
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
        container.insertAdjacentHTML('beforeend', html);
        medicineIndex++;
    });
    
    // Remove Test Row
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-test')) {
            const row = e.target.closest('.test-row');
            if (document.querySelectorAll('.test-row').length > 1) {
                row.remove();
            }
        }
    });
    
    // Remove Medicine Row
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