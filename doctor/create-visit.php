<?php

/**
 * Create Patient Visit
 * Clinic EMR System
 */

// Enable error display for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/auth.php';
requireDoctor();
require_once __DIR__ . '/../includes/functions.php';   // ADD THIS
require_once __DIR__ . '/../includes/csrf.php';   // ADD THIS
require_once __DIR__ . '/../includes/logger.php';   // ADD THIS - NEW LOGGER
require_once __DIR__ . '/../classes/Patient.php';
require_once __DIR__ . '/../classes/Session.php';
require_once __DIR__ . '/../classes/Visit.php';
require_once __DIR__ . '/../classes/Test.php';
require_once __DIR__ . '/../classes/Prescription.php';
require_once __DIR__ . '/../classes/Invoice.php';

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

// initialize lab test handler so we can load type lists
require_once __DIR__ . '/../classes/LabTest.php';
$labTest = new LabTest();

// fetch active test types (id/name pairs) for rendering
$testTypes = $labTest->getTypeList(true);

// build a lookup map by type name so we can convert saved string -> id later
$typeNameToId = [];
foreach ($testTypes as $tt) {
    $typeNameToId[$tt['type_name']] = $tt['type_id'];
}

if (!$patient) {
    redirect(APP_URL . '/doctor/patient-queue.php', 'danger', 'Patient not found.');
}

$doctorId = User::getUserId();
$errors = [];
$visitId = null;

// Check if visitId is in URL (from redirect after save)
if (!empty($_GET['visit_id'])) {
    $visitId = (int)$_GET['visit_id'];
    $_SESSION['current_visit_id'] = $visitId;
    Logger::debug("Retrieved visitId from URL: " . $visitId);
}
// Check if visitId is passed in form (from hidden input)
elseif (!empty($_POST['visit_id'])) {
    $visitId = (int)$_POST['visit_id'];
    $_SESSION['current_visit_id'] = $visitId;
    Logger::debug("Retrieved visitId from POST: " . $visitId);
}
// Or check if it's stored in session (from previous save on this page)
elseif (!empty($_SESSION['current_visit_id']) && !empty($_SESSION['current_patient_id']) && $_SESSION['current_patient_id'] == $patientId) {
    $visitId = $_SESSION['current_visit_id'];
    Logger::debug("Retrieved visitId from SESSION: " . $visitId);
}

// Always set current patient ID in session to detect patient switches
$_SESSION['current_patient_id'] = $patientId;

// Validate that the visit belongs to this patient (safety check)
if ($visitId) {
    $visitCheck = $visitObj->getById($visitId);
    if (!$visitCheck || $visitCheck['patient_id'] != $patientId) {
        Logger::info("Visit ID " . $visitId . " does not belong to patient " . $patientId . " - clearing");
        $visitId = null;
        unset($_SESSION['current_visit_id']);
    }
}

// If no visitId found yet, check if there's an existing "In Progress" visit for this patient
if (!$visitId) {
    Logger::debug("No visitId found - checking for existing In Progress visit for patient " . $patientId);
    $existingVisit = $visitObj->getPatientInProgressVisit($patientId);
    if ($existingVisit) {
        $visitId = $existingVisit['visit_id'];
        $_SESSION['current_visit_id'] = $visitId;
        Logger::info("Found existing In Progress visit: " . $visitId);
    }
}

if ($sessionId) {
    $sessionObj->updatePatientStatus($sessionId, $patientId, 'In Progress');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCSRF();

    $action = $_POST['action'] ?? '';
    $db = Database::getInstance();

    // Debug: Log what we received
    Logger::debug("=== VISIT SAVE DEBUG ===");
    Logger::debug("POST visit_id: " . ($_POST['visit_id'] ?? 'NONE'));
    Logger::debug("SESSION visit_id: " . ($_SESSION['current_visit_id'] ?? 'NONE'));
    Logger::debug("POST action: " . $action);

    try {
        Database::getInstance()->beginTransaction();

        // Create or update visit
        $visitData = [
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

        // If visitId exists, UPDATE; otherwise CREATE new
        if ($visitId) {
            Logger::info(">>> UPDATING existing visit ID: " . $visitId);
            // Update existing visit (no visit_code change)
            $updateResult = $visitObj->update($visitId, $visitData);
            Logger::info(">>> Update result: " . ($updateResult ? 'SUCCESS' : 'FAILED'));

            if (!$updateResult) {
                throw new Exception('Failed to update visit record.');
            }
        } else {
            Logger::info(">>> CREATING new visit (no visitId found)");
            // Create new visit
            $visitNewCode = $visitObj->generateVisitCode();
            Logger::info(">>> Generated visit code: " . $visitNewCode);

            $visitData['visit_code'] = $visitNewCode;
            $visitId = $visitObj->create($visitData);

            if (!$visitId) {
                throw new Exception('Failed to create visit record.');
            }

            Logger::info(">>> New visit created with ID: " . $visitId);
            $_SESSION['current_visit_id'] = $visitId;
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

        Logger::debug("Saving vital signs: " . print_r($vitalData, true));

        try {
            $vitalResult = $visitObj->saveVitalSigns($vitalData);
            Logger::info("Vital signs saved successfully, ID: " . $vitalResult);
        } catch (Exception $e) {
            Logger::error("Error saving vital signs: " . $e->getMessage());
            throw $e;
        }

        // Delete any tests that were removed by the user
        if (!empty($_POST['tests_to_delete'])) {
            Logger::debug("Deleting tests: " . implode(',', $_POST['tests_to_delete']));
            foreach ($_POST['tests_to_delete'] as $tid) {
                $testObj->delete((int)$tid);
            }
        }

        // Save tests
        if (!empty($_POST['tests'])) { // patient_tests table
            Logger::debug("Processing tests...");
            $newTests = [];

            foreach ($_POST['tests'] as $key => $test) {
                if (!empty($test['test_type'])) {
                    // convert numeric type id to human name if necessary
                    $typeInput = $test['test_type'];
                    if (is_numeric($typeInput)) {
                        $typeRec = $labTest->getTestTypeById((int)$typeInput);
                        $typeName = $typeRec ? $typeRec['type_name'] : '';
                    } else {
                        $typeName = $typeInput;
                    }

                    // Check if it's a new test (numeric key) or existing test (saved_XXX key)
                    if (strpos($key, 'saved_') === 0) {
                        // Existing test - update it
                        $testId = (int)str_replace('saved_', '', $key);
                        $updateData = [
                            'test_type' => $typeName,
                            'test_name' => $test['test_name'] ?? $typeName,
                            'instructions' => $test['instructions'] ?? null,
                            'urgency' => $test['urgency'] ?? 'Routine'
                        ];
                        Logger::debug("Updating existing test ID " . $testId);
                        $db->update(
                            "UPDATE patient_tests SET test_type=?, test_name=?, instructions=?, urgency=? WHERE test_id=?",
                            [
                                $updateData['test_type'],
                                $updateData['test_name'],
                                $updateData['instructions'],
                                $updateData['urgency'],
                                $testId
                            ]
                        );
                    } else {
                        // New test - add to creation list
                        $newTests[] = [
                            'test_type' => $typeName,
                            'test_name' => $test['test_name'] ?? $typeName,
                            'instructions' => $test['instructions'] ?? null,
                            'urgency' => $test['urgency'] ?? 'Routine'
                        ];
                    }
                }
            }

            // Only create new tests if there are any
            if (!empty($newTests)) {
                Logger::info("Saving " . count($newTests) . " new tests");
                try {
                    $testObj->createMultiple($visitId, $patientId, $newTests);
                    Logger::info("New tests saved successfully");
                } catch (Exception $e) {
                    Logger::error("Error saving tests: " . $e->getMessage());
                    throw $e;
                }
            }
        }

        // Save prescription and medicines
        // Load existing prescription for this visit if it exists (needed for update logic)
        $existingPrescription = null;
        if ($visitId) {
            $existingPrescription = $prescriptionObj->getByVisitId($visitId);
            if ($existingPrescription) {
                Logger::debug("Found existing prescription for visit " . $visitId . ": ID " . $existingPrescription['prescription_id']);
            }
        }

        // first delete any medicines marked removed by the user
        if (!empty($_POST['medicines_to_delete'])) {
            foreach ($_POST['medicines_to_delete'] as $mid) {
                $prescriptionObj->deleteMedicine((int)$mid);
                Logger::debug("Deleted medicine ID " . $mid);
            }
        }

        if (!empty($_POST['medicines'])) {
            Logger::debug("Processing medicines...");
            try {
                // Check if prescription already exists
                if ($existingPrescription && !empty($existingPrescription['prescription_id'])) {
                    // Update existing prescription
                    $prescriptionId = $existingPrescription['prescription_id'];
                    Logger::debug("Updating existing prescription ID: " . $prescriptionId);
                    $db->update(
                        "UPDATE patient_prescriptions SET notes = ? WHERE prescription_id = ?",
                        [sanitize($_POST['prescription_notes'] ?? ''), $prescriptionId]
                    );
                } else {
                        // Create new prescription
                        $prescriptionData = [
                            'prescription_code' => $prescriptionObj->generatePrescriptionCode(),
                            'visit_id' => $visitId,
                            'patient_id' => $patientId,
                            'doctor_id' => $doctorId,
                            'prescription_date' => date('Y-m-d'),
                            'notes' => sanitize($_POST['prescription_notes'] ?? '')
                        ];
                        Logger::debug("Creating new prescription with data: " . print_r($prescriptionData, true));
                        $prescriptionId = $prescriptionObj->create($prescriptionData);
                        Logger::info("Prescription created, ID: " . $prescriptionId);
                    }

                    if ($prescriptionId) {
                        // Process medicines
                        $newMedicines = [];
                        foreach ($_POST['medicines'] as $key => $med) {
                            if (!empty($med['medicine_name'])) {
                                if (strpos($key, 'saved_') === 0) {
                                    // Existing prescription medicine – update the record in
                                    // patient_prescription_medicines, not the master medicines list.
                                    $medicineId = (int)str_replace('saved_', '', $key);
                                    $updateData = [
                                        'medicine_name' => sanitize($med['medicine_name']),
                                        'dose' => sanitize($med['dose'] ?? ''),
                                        'frequency' => sanitize($med['frequency'] ?? ''),
                                        'duration_days' => (int)($med['duration_days'] ?? 0),
                                        'route' => $med['route'] ?? 'Oral',
                                        'instructions' => sanitize($med['instructions'] ?? '')
                                    ];
                                    Logger::debug("Updating existing prescription medicine ID " . $medicineId);
                                    $db->update(
                                        "UPDATE patient_prescription_medicines SET medicine_name=?, dose=?, frequency=?, duration_days=?, route=?, instructions=? WHERE medicine_id=?",
                                        [
                                            $updateData['medicine_name'],
                                            $updateData['dose'],
                                            $updateData['frequency'],
                                            $updateData['duration_days'],
                                            $updateData['route'],
                                            $updateData['instructions'],
                                            $medicineId
                                        ]
                                    );
                                } else {
                                    // New medicine - add to creation list
                                    $newMedicines[] = [
                                        'medicine_name' => sanitize($med['medicine_name']),
                                        'dose' => sanitize($med['dose'] ?? ''),
                                        'frequency' => sanitize($med['frequency'] ?? ''),
                                        'duration_days' => (int)($med['duration_days'] ?? 0),
                                        'route' => $med['route'] ?? 'Oral',
                                        'instructions' => sanitize($med['instructions'] ?? '')
                                    ];
                                }
                            }
                        }

                        // Only add new medicines if there are any
                        if (!empty($newMedicines)) {
                            Logger::info("Adding " . count($newMedicines) . " new medicines");
                            $prescriptionObj->addMedicines($prescriptionId, $newMedicines);
                            Logger::info("New medicines added successfully");
                        }
                    }
                } catch (Exception $e) {
                    Logger::error("Error saving prescription/medicines: " . $e->getMessage());
                    throw $e;
                }
            }

        // Update queue status if completing
        if ($action === 'complete' && $sessionId) {
            $sessionObj->updatePatientStatus($sessionId, $patientId, 'Completed');
        }

        Database::getInstance()->commit();

        // Auto-save invoice when visit is completed (silently, without showing message)
        if ($action === 'complete') {
            try {
                $invoice = new Invoice();
                
                // Build invoice items from consultation fee and medicines
                $invoiceItems = [];
                
                // Add consultation fee as an item
                $invoiceItems[] = [
                    'type' => 'Consultation',
                    'description' => 'Doctor Consultation',
                    'reference_id' => null,
                    'quantity' => 1,
                    'unit_price' => 500.00  // Default consultation fee
                ];
                
                // Add prescribed medicines
                if (isset($prescriptionId)) {
                    $prescriptionMeds = $prescriptionObj->getMedicines($prescriptionId);
                    foreach ($prescriptionMeds as $med) {
                        // Get medicine price from medicines table
                        $medDetails = $db->fetchOne("SELECT mrp FROM medicines WHERE medicine_name = ?", [$med['medicine_name']]);
                        $medPrice = $medDetails['mrp'] ?? 100.00;  // Default if not found
                        
                        $invoiceItems[] = [
                            'type' => 'Medicine',
                            'description' => $med['medicine_name'],
                            'reference_id' => $med['medicine_id'] ?? null,
                            'quantity' => 1,
                            'unit_price' => $medPrice
                        ];
                    }
                }
                
                // Create invoice if there are items
                if (!empty($invoiceItems)) {
                    try {
                        $newInvoiceId = $invoice->createFromVisit($visitId, $invoiceItems, [
                            'patient_id' => $patientId,
                            'doctor_id' => $doctorId,
                            'tax_percentage' => 10,
                            'discount_amount' => 0,
                            'created_by' => $doctorId
                        ]);
                        Logger::info("Auto-saved invoice ID " . $newInvoiceId . " for visit " . $visitId);
                    } catch (Exception $ie) {
                        Logger::warning("Failed to auto-save invoice: " . $ie->getMessage());
                        // Don't throw - invoice creation shouldn't break the visit completion
                    }
                }
            } catch (Exception $e) {
                Logger::warning("Error in invoice auto-save: " . $e->getMessage());
                // Don't throw - invoice save shouldn't break the main flow
            }
        }

        // Log activity
        $user = new User();
        $user->logActivity('Create Visit', 'patient_visits', $visitId, 'Created visit for patient ' . $patientId);

        if ($action === 'complete') {
            // Clear visit session on complete
            unset($_SESSION['current_visit_id']);

            // Redirect to print prescription if exists
            if (isset($prescriptionId)) {
                redirect(APP_URL . '/doctor/print-prescription.php?id=' . $prescriptionId, 'success', 'Visit completed successfully!');
            } else {
                redirect(APP_URL . '/doctor/patient-queue.php', 'success', 'Visit completed successfully!');
            }
        } else {
            // Save (not complete) - reload page with visit created for further editing
            setFlash('success', 'Visit saved! Continue editing or click "Complete & Print" when done.');
            // Redirect with visitId in URL for proper session handling
            redirect(APP_URL . '/doctor/create-visit.php?patient_id=' . $patientId . '&visit_id=' . $visitId, 'success', 'Visit saved!');
        }
    } catch (Exception $e) {
        Database::getInstance()->rollback();
        $errors[] = 'An error occurred: ' . $e->getMessage();
    }
}

$pageTitle = 'Visit - ' . $patient['full_name'];

// Load existing visit data if visitId is set
$existingVitalSigns = null;
$currentVisit = null;
$existingTests = [];
$existingPrescription = null;
$existingMedicines = [];

if ($visitId) {
    Logger::debug("Loading existing vital signs for visit ID: " . $visitId);
    $existingVitalSigns = $visitObj->getVitalSigns($visitId);
    if ($existingVitalSigns) {
        Logger::info("Found existing vital signs: " . print_r($existingVitalSigns, true));
    }

    // Also get the full visit data
    $currentVisit = $visitObj->getById($visitId);
    if ($currentVisit) {
        Logger::info("Loaded visit data - Symptoms: " . ($currentVisit['symptoms'] ?? 'none'));
    }

    // Load existing tests
    $db = Database::getInstance();
    $existingTests = $db->fetchAll(
        "SELECT * FROM patient_tests WHERE visit_id = ? ORDER BY test_id",
        [$visitId]
    );
    Logger::info("Found " . count($existingTests) . " existing tests");

    // Load existing prescription
    $existingPrescription = $prescriptionObj->getByVisitId($visitId);
    if ($existingPrescription) {
        Logger::info("Found existing prescription ID: " . $existingPrescription['prescription_id']);
        $existingMedicines = $prescriptionObj->getMedicines($existingPrescription['prescription_id']);
        Logger::info("Found " . count($existingMedicines) . " medicines");
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>



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

<?php
// intercept any flash message so we can incorporate it into the visit status banner when appropriate
$flash = getFlash();
if ($flash && !($visitId && $flash['type'] === 'success')) {
    // show normal alert if not editing or if not a success message during edit
    echo "<div class='alert alert-{$flash['type']} alert-dismissible fade show' role='alert'>" . htmlspecialchars($flash['message']) . "<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
}

?>

<!-- Patient Info Banner -->
<div class="card mb-4 bg-primary text-white shadow-sm">
    <div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h4 class="mb-1"><?php echo htmlspecialchars($patient['full_name']); ?></h4>
                <div class="d-flex flex-wrap gap-3 fs-6">
                    <span><i class="bi bi-upc me-1"></i><?php echo $patient['patient_code']; ?></span>
                    <span><i class="bi bi-calendar3 me-1"></i><?php echo $patient['age']; ?>y</span>
                    <span><i class="bi bi-droplet me-1"></i><?php echo $patient['blood_group']; ?></span>
                    <span><i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($patient['phone']); ?></span>
                </div>
                <?php if ($patient['allergies']): ?>
                    <div class="mt-2">
                        <span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i>Allergies: <?php echo htmlspecialchars($patient['allergies']); ?></span>
                    </div>
                <?php endif; ?>
            </div>
            <div class="mt-2 mt-md-0">
                <a href="patient-history.php?id=<?php echo $patientId; ?>" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-clock-history me-1"></i>View History
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Visit Status Banner -->
<?php if ($visitId): ?>
    <div class="alert alert-info alert-dismissible fade show d-flex justify-content-between align-items-center shadow-sm">
        <div>
            <?php if (isset($flash) && $flash && $flash['type'] === 'success'): ?>
                <strong><?php echo htmlspecialchars($flash['message']); ?></strong><br>
            <?php endif; ?>
            <i class="bi bi-pencil-square me-2"></i>
            <strong>Editing Visit:</strong> Continuing from previous save. Your changes will be updated.
            <?php if ($existingVitalSigns): ?>
                <br><small class="text-info-emphasis">✓ Vital signs loaded</small>
            <?php endif; ?>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php else: ?>
    <div class="alert alert-warning alert-dismissible fade show d-flex justify-content-between align-items-center shadow-sm">
        <div>
            <i class="bi bi-plus-circle me-2"></i>
            <strong>New Visit:</strong> Click "Save Visit" to create, then click "Complete & Print" when done.
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="POST" action="" id="visitForm">
    <?php echo csrfField(); ?>

    <!-- Store visitId for updating existing visit -->
    <?php if ($visitId): ?>
        <input type="hidden" name="visit_id" value="<?php echo $visitId; ?>">
    <?php endif; ?>

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

    <!-- Form section re‑styled with vertical navigation for a cleaner, modern layout -->
    <div class="card mb-4 shadow-sm">
        <div class="row g-0">
            <!-- side menu -->
            <div class="col-md-3 bg-light p-3">
                <ul class="nav nav-pills flex-column" id="visitTabs" role="tablist">
                    <li class="nav-item mb-2" role="presentation">
                        <button class="nav-link active" id="vital-signs-tab" data-bs-toggle="pill" data-bs-target="#vital-signs" type="button" role="tab" aria-controls="vital-signs" aria-selected="true">
                            <i class="bi bi-activity me-1"></i>Vital Signs
                        </button>
                    </li>
                    <li class="nav-item mb-2" role="presentation">
                        <button class="nav-link" id="symptoms-tab" data-bs-toggle="pill" data-bs-target="#symptoms" type="button" role="tab" aria-controls="symptoms" aria-selected="false">
                            <i class="bi bi-clipboard2-pulse me-1"></i>Symptoms &amp; Diagnosis
                        </button>
                    </li>
                    <li class="nav-item mb-2" role="presentation">
                        <button class="nav-link" id="tests-tab" data-bs-toggle="pill" data-bs-target="#tests" type="button" role="tab" aria-controls="tests" aria-selected="false">
                            <i class="bi bi-clipboard-check me-1"></i>Test Requests
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="prescription-tab" data-bs-toggle="pill" data-bs-target="#prescription" type="button" role="tab" aria-controls="prescription" aria-selected="false">
                            <i class="bi bi-capsule me-1"></i>Prescription
                        </button>
                    </li>
                </ul>
            </div>
            <!-- content area -->
            <div class="col-md-9">
                <div class="tab-content p-3" id="visitTabsContent">
                    <!-- TAB 1: VITAL SIGNS -->
                    <div class="tab-pane fade show active" id="vital-signs" role="tabpanel" aria-labelledby="vital-signs-tab">
                        <div class="card-body">
                            <h6 class="mb-4 text-muted"><i class="bi bi-thermometer me-2"></i>Monitor and record patient's vital signs</h6>
                            <div class="row g-3">
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label fw-bold"><i class="bi bi-thermometer-half me-1"></i>Temperature (°C)</label>
                                    <input type="number" step="0.1" class="form-control" name="temperature"
                                        placeholder="36.5" min="30" max="45"
                                        value="<?php echo $existingVitalSigns['temperature'] ?? ''; ?>">
                                    <span class="vital-sign-status" id="tempStatus"></span>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label fw-bold"><i class="bi bi-heart me-1"></i>BP Systolic</label>
                                    <input type="number" class="form-control" name="bp_systolic" id="bp_systolic"
                                        placeholder="120" min="60" max="250"
                                        value="<?php echo $existingVitalSigns['blood_pressure_systolic'] ?? ''; ?>">
                                    <span class="vital-sign-status" id="bpSystolicStatus"></span>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label fw-bold"><i class="bi bi-heart me-1"></i>BP Diastolic</label>
                                    <input type="number" class="form-control" name="bp_diastolic" id="bp_diastolic"
                                        placeholder="80" min="40" max="150"
                                        value="<?php echo $existingVitalSigns['blood_pressure_diastolic'] ?? ''; ?>">
                                    <span class="vital-sign-status" id="bpDiastolicStatus"></span>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label fw-bold"><i class="bi bi-heart-pulse me-1"></i>Pulse Rate (bpm)</label>
                                    <input type="number" class="form-control" name="pulse_rate" id="pulse_rate"
                                        placeholder="72" min="30" max="200"
                                        value="<?php echo $existingVitalSigns['pulse_rate'] ?? ''; ?>">
                                    <span class="vital-sign-status" id="pulseStatus"></span>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label fw-bold"><i class="bi bi-weight me-1"></i>Weight (kg)</label>
                                    <input type="number" step="0.1" class="form-control" name="weight" id="weight"
                                        placeholder="70" min="1" max="500"
                                        value="<?php echo $existingVitalSigns['weight'] ?? ($patient['weight'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label fw-bold"><i class="bi bi-arrow-up me-1"></i>Height (cm)</label>
                                    <input type="number" step="0.1" class="form-control" name="height" id="height"
                                        placeholder="170" min="30" max="250"
                                        value="<?php echo $existingVitalSigns['height'] ?? ($patient['height'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label fw-bold"><i class="bi bi-lungs me-1"></i>SpO₂ (%)</label>
                                    <input type="number" class="form-control" name="oxygen_saturation" id="spo2"
                                        placeholder="98" min="50" max="100"
                                        value="<?php echo $existingVitalSigns['oxygen_saturation'] ?? ''; ?>">
                                    <span class="vital-sign-status" id="spo2Status"></span>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label fw-bold"><i class="bi bi-wind me-1"></i>Respiratory Rate</label>
                                    <input type="number" class="form-control" name="respiratory_rate" id="respiratory_rate"
                                        placeholder="16" min="5" max="60"
                                        value="<?php echo $existingVitalSigns['respiratory_rate'] ?? ''; ?>">
                                    <span class="vital-sign-status" id="respRateStatus"></span>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-bold"><i class="bi bi-calculator me-1"></i>BMI (Calculated)</label>
                                    <div id="bmiResult" class="bmi-display">N/A</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: SYMPTOMS & DIAGNOSIS -->
                    <div class="tab-pane fade" id="symptoms" role="tabpanel" aria-labelledby="symptoms-tab">
                        <div class="card-body">
                            <div class="row g-4">
                                <div class="col-md-12">
                                    <label class="form-label fw-bold"><i class="bi bi-bug me-2"></i>Symptoms / Chief Complaint</label>
                                    <textarea class="form-control" name="symptoms" rows="4"
                                        placeholder="Describe patient's symptoms in detail..."><?php echo htmlspecialchars($currentVisit['symptoms'] ?? $_POST['symptoms'] ?? ''); ?></textarea>
                                    <small class="text-muted">Be specific about onset, duration, and severity</small>
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label fw-bold"><i class="bi bi-search me-2"></i>Diagnosis</label>
                                    <textarea class="form-control" name="diagnosis" rows="4"
                                        placeholder="Enter your clinical diagnosis..."><?php echo htmlspecialchars($currentVisit['diagnosis'] ?? $_POST['diagnosis'] ?? ''); ?></textarea>
                                    <small class="text-muted">Include primary and secondary diagnoses if applicable</small>
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label fw-bold"><i class="bi bi-pencil me-2"></i>Clinical Notes</label>
                                    <textarea class="form-control" name="notes" rows="3"
                                        placeholder="Additional observations, findings, or clinical notes..."><?php echo htmlspecialchars($currentVisit['notes'] ?? $_POST['notes'] ?? ''); ?></textarea>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold"><i class="bi bi-calendar-check me-2"></i>Follow-up Date</label>
                                    <input type="date" class="form-control" name="follow_up_date"
                                        min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                                        value="<?php echo $currentVisit['follow_up_date'] ?? ''; ?>">
                                    <small class="text-muted">When should the patient return for follow-up?</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: TEST REQUESTS -->
                    <div class="tab-pane fade" id="tests" role="tabpanel" aria-labelledby="tests-tab">
                        <div class="card-body">
                            <!-- Header with badge and add button -->
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div>
                                    <h5 class="mb-0">Lab Tests
                                        <?php if (!empty($existingTests)): ?>
                                            <span class="badge bg-info text-dark"><?php echo count($existingTests); ?> saved</span>
                                        <?php endif; ?>
                                    </h5>
                                </div>
                                <button type="button" class="btn btn-primary btn-sm" id="addTestBtn">
                                    <i class="bi bi-plus-circle me-1"></i> Add Test
                                </button>
                            </div>

                            <!-- Tests container - where new tests will be added -->
                            <div id="testsContainer">
                                <?php foreach ($existingTests as $savedTest): ?>
                                    <?php
                                    // convert stored type name back to id so we can pre-select
                                    $savedTypeId = $typeNameToId[$savedTest['test_type']] ?? '';
                                    ?>
                                    <div class="test-row card mb-3" data-row="saved_<?php echo $savedTest['test_id']; ?>" style="background-color: #e3f2fd; border-left: 4px solid #2196F3;">
                                        <div class="card-body">
                                            <div class="row g-3">
                                                <div class="col-md-4">
                                                    <label class="form-label small fw-bold">Test Type</label>
                                                    <select class="form-select form-select-sm test-type-select" name="tests[saved_<?php echo $savedTest['test_id']; ?>][test_type]" data-row="saved_<?php echo $savedTest['test_id']; ?>">
                                                        <option value="">Select Type</option>
                                                        <?php foreach ($testTypes as $type): ?>
                                                            <option value="<?php echo $type['type_id']; ?>" <?php echo $savedTypeId == $type['type_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($type['type_name']); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label small fw-bold">Test Name</label>
                                                    <select class="form-select form-select-sm test-name-select" name="tests[saved_<?php echo $savedTest['test_id']; ?>][test_name]">
                                                        <option value="<?php echo htmlspecialchars($savedTest['test_name']); ?>" selected><?php echo htmlspecialchars($savedTest['test_name']); ?></option>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label small fw-bold">Urgency</label>
                                                    <select class="form-select form-select-sm" name="tests[saved_<?php echo $savedTest['test_id']; ?>][urgency]">
                                                        <option value="Routine" <?php echo $savedTest['urgency'] === 'Routine' ? 'selected' : ''; ?>>Routine</option>
                                                        <option value="Urgent" <?php echo $savedTest['urgency'] === 'Urgent' ? 'selected' : ''; ?>>Urgent</option>
                                                        <option value="STAT" <?php echo $savedTest['urgency'] === 'STAT' ? 'selected' : ''; ?>>STAT</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-1 d-flex align-items-end">
                                                    <button type="button" class="btn btn-sm btn-outline-danger w-100 remove-test" title="Remove test" onclick="if(confirm('Delete this test?')) { this.closest('.test-row').remove(); }">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div><!-- End of testsContainer -->

                            <!-- container to hold ids of tests marked for deletion -->
                            <div id="testsToDeleteContainer"></div>
                    <div id="medicinesToDeleteContainer"></div>

                            <!-- Form for adding new tests -->
                            <div class="test-row card" data-row="0" style="background-color: #f8f9fa; border: 2px dashed #dee2e6;">
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold">Test Type</label>
                                            <select class="form-select form-select-sm test-type-select" name="tests[0][test_type]" data-row="0">
                                                <option value="">Select Type</option>
                                                <?php foreach ($testTypes as $type): ?>
                                                    <option value="<?php echo $type['type_id']; ?>"><?php echo htmlspecialchars($type['type_name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold">Test Name</label>
                                            <select class="form-select form-select-sm test-name-select" name="tests[0][test_name]">
                                                <option value="">Select or type name</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small fw-bold">Urgency</label>
                                            <select class="form-select form-select-sm" name="tests[0][urgency]">
                                                <option value="Routine">Routine</option>
                                                <option value="Urgent">Urgent</option>
                                                <option value="STAT">STAT</option>
                                            </select>
                                        </div>
                                        <div class="col-md-1 d-flex align-items-end">
                                            <button type="button" class="btn btn-sm btn-outline-danger w-100 remove-test" title="Remove">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 4: PRESCRIPTION -->
                    <div class="tab-pane fade" id="prescription" role="tabpanel" aria-labelledby="prescription-tab">
                        <div class="card-body">
                            <!-- Header with badge and add button -->
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div>
                                    <h5 class="mb-0">Medicines
                                        <?php if (!empty($existingMedicines)): ?>
                                            <span class="badge bg-info text-dark"><?php echo count($existingMedicines); ?> saved</span>
                                        <?php endif; ?>
                                    </h5>
                                </div>
                                <button type="button" class="btn btn-primary btn-sm" id="addMedicineBtn">
                                    <i class="bi bi-plus-circle me-1"></i> Add Medicine
                                </button>
                            </div>

                            <!-- Medicines container - where new medicines will be added -->
                            <div id="medicinesContainer">
                                <!-- Existing saved medicines -->
                                <?php foreach ($existingMedicines as $med): ?>
                                    <div class="medicine-row card mb-3" style="background-color: #f3e5f5; border-left: 4px solid #9C27B0;" data-row="saved_<?php echo $med['medicine_id']; ?>">
                                        <div class="card-body">
                                            <div class="row g-3">
                                                <div class="col-md-4">
                                                    <label class="form-label small fw-bold">Medicine Name</label>
                                                    <select class="form-select form-select-sm medicine-select" name="medicines[saved_<?php echo $med['medicine_id']; ?>][medicine_name]">
                                                        <option value="">Select Medicine</option>
                                                        <?php foreach (getMedicines() as $medicine): ?>
                                                            <option value="<?php echo htmlspecialchars($medicine['medicine_name']); ?>"
                                                                <?php echo $med['medicine_name'] === $medicine['medicine_name'] ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($medicine['medicine_name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label small fw-bold">Dose</label>
                                                    <input type="text" class="form-control form-control-sm" name="medicines[saved_<?php echo $med['medicine_id']; ?>][dose]"
                                                        placeholder="e.g., 500mg" value="<?php echo htmlspecialchars($med['dose']); ?>">
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label small fw-bold">Route</label>
                                                    <select class="form-select form-select-sm" name="medicines[saved_<?php echo $med['medicine_id']; ?>][route]">
                                                        <?php foreach (getMedicineRoutes() as $route): ?>
                                                            <option value="<?php echo $route; ?>" <?php echo $med['route'] === $route ? 'selected' : ''; ?>><?php echo $route; ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label small fw-bold">Frequency</label>
                                                    <select class="form-select form-select-sm" name="medicines[saved_<?php echo $med['medicine_id']; ?>][frequency]">
                                                        <option value="">-- Select --</option>
                                                        <?php foreach (getFrequencies() as $freq): ?>
                                                            <option value="<?php echo $freq; ?>" <?php echo $med['frequency'] === $freq ? 'selected' : ''; ?>><?php echo $freq; ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-1">
                                                    <label class="form-label small fw-bold">Days</label>
                                                    <input type="number" class="form-control form-control-sm" name="medicines[saved_<?php echo $med['medicine_id']; ?>][duration_days]"
                                                        placeholder="7" min="1" max="365" value="<?php echo $med['duration_days']; ?>">
                                                </div>
                                                <div class="col-md-1 d-flex align-items-end">
                                                    <button type="button" class="btn btn-sm btn-outline-danger w-100 remove-medicine" title="Remove medicine"
                                                        onclick="if(confirm('Delete this medicine?')) { this.closest('.medicine-row').remove(); }">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="row mt-2">
                                                <div class="col-md-12">
                                                    <label class="form-label small fw-bold">Instructions</label>
                                                    <input type="text" class="form-control form-control-sm" name="medicines[saved_<?php echo $med['medicine_id']; ?>][instructions]"
                                                        placeholder="e.g., After meals, with water" value="<?php echo htmlspecialchars($med['instructions'] ?? ''); ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div><!-- End of medicinesContainer -->

                            <!-- Form for adding new medicines -->
                            <div class="medicine-row card" style="background-color: #f8f9fa; border: 2px dashed #dee2e6;">
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold">Medicine Name</label>
                                            <select class="form-select form-select-sm medicine-select" name="medicines[0][medicine_name]">
                                                <option value="">Select Medicine</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small fw-bold">Dose</label>
                                            <input type="text" class="form-control form-control-sm" name="medicines[0][dose]" placeholder="e.g., 500mg">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small fw-bold">Route</label>
                                            <select class="form-select form-select-sm" name="medicines[0][route]">
                                                <?php foreach (getMedicineRoutes() as $route): ?>
                                                    <option value="<?php echo $route; ?>"><?php echo $route; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small fw-bold">Frequency</label>
                                            <select class="form-select form-select-sm" name="medicines[0][frequency]">
                                                <option value="">-- Select --</option>
                                                <?php foreach (getFrequencies() as $freq): ?>
                                                    <option value="<?php echo $freq; ?>"><?php echo $freq; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-1">
                                            <label class="form-label small fw-bold">Days</label>
                                            <input type="number" class="form-control form-control-sm" name="medicines[0][duration_days]" placeholder="7" min="1" max="365">
                                        </div>
                                        <div class="col-md-1 d-flex align-items-end">
                                            <button type="button" class="btn btn-sm btn-outline-danger w-100 remove-medicine" title="Remove">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-md-12">
                                            <label class="form-label small fw-bold">Instructions</label>
                                            <input type="text" class="form-control form-control-sm" name="medicines[0][instructions]" placeholder="e.g., After meals, with water">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Prescription Notes -->
                            <div class="card mt-4" style="background-color: #fff3cd; border: 1px solid #ffc107;">
                                <div class="card-body">
                                    <label class="form-label fw-bold mb-2"><i class="bi bi-pencil-fill me-2"></i>Prescription Notes</label>
                                    <textarea class="form-control" name="prescription_notes" rows="4"
                                        placeholder="Add any additional notes, warnings, or advice for the patient..."><?php echo htmlspecialchars($existingPrescription['notes'] ?? ''); ?></textarea>
                                    <small class="text-muted d-block mt-2">These notes will appear on the printed prescription</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->

</form>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    // expose DB-driven test type list to JS
    var testTypes = <?php echo json_encode($testTypes, JSON_UNESCAPED_SLASHES); ?>;
    
    // Load medicines list from database
    var medicinesList = [];
    var medicinesLoaded = false;
    
    function loadMedicines() {
        if (medicinesLoaded) return Promise.resolve(medicinesList);
        
        return fetch('ajax/visit-ajax.php?action=search_medicines&term=')
            .then(r => r.json())
            .then(data => {
                medicinesList = data || [];
                medicinesLoaded = true;
                console.log('Loaded ' + medicinesList.length + ' medicines');
                return medicinesList;
            })
            .catch(err => {
                console.error('Error loading medicines:', err);
                return [];
            });
    }

    // Initialize Select2 when jQuery is ready
    $(document).ready(function() {
        // Pre-load medicines on page load
        loadMedicines();
        
        // helper to attach Select2 autocomplete to a medicine select element
        function initMedicineSelect(el) {
            const currentVal = el.value;
            $(el).select2({
                theme: 'bootstrap-5',
                width: '100%',
                allowClear: true,
                placeholder: 'Search and select medicine...',
                containerCss: {
                    'width': '100%'
                },
                minimumInputLength: 0,
                ajax: {
                    url: 'ajax/visit-ajax.php',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            action: 'search_medicines',
                            term: params.term || ''
                        };
                    },
                    processResults: function(data) {
                        // convert server format to Select2 format
                        return {
                            results: data.map(function(m) {
                                return {
                                    id: m.medicine_name,
                                    text: m.label || m.medicine_name,
                                    _raw: m
                                };
                            })
                        };
                    }
                }
            });
            // restore existing selection if any
            if (currentVal) {
                $(el).val(currentVal).trigger('change');
            }
        }

        // Initialize any existing medicine selects on page load
        $('.medicine-select').each(function() {
            initMedicineSelect(this);
        });

        // prepare any existing test-name selects with select2 (tagging enabled so doctor can type other names)
        $('.test-name-select').select2({
            theme: 'bootstrap-5',
            width: '100%',
            tags: true,
            allowClear: true,
            placeholder: 'Choose or type test name',
            containerCss: {
                'width': '100%'
            }
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        let testIndex = 1;
        let medicineIndex = 1;

        // Light background colors for medicine cards
        const medicineColors = [
            '#e8f4f8', // Light Blue
            '#f0f8e8', // Light Green
            '#fff8e8', // Light Yellow
            '#ffe8f0', // Light Pink
            '#f0e8f8', // Light Purple
            '#f8e8e8', // Light Red
            '#e8f8f0' // Light Cyan
        ];

        // helper to generate options html from global testTypes array
        function renderTypeOptions() {
            let opts = '<option value="">Select Type</option>';
            testTypes.forEach(function(t) {
                opts += `<option value="${t.type_id}">${t.type_name}</option>`;
            });
            return opts;
        }

        // helper to populate name select for a row
        function populateNamesForRow(rowKey, typeId, selectedName) {
            const sel = document.querySelector(`.test-row[data-row="${rowKey}"] .test-name-select`);
            if (!sel) return;
            console.debug('populateNamesForRow', {
                rowKey,
                typeId,
                selectedName
            });
            if (!typeId) {
                // no type selected; preserve any existing name so it doesn't vanish
                let html = '<option value="">Select or type name</option>';
                if (selectedName) {
                    html += `<option value="${selectedName}" selected>${selectedName}</option>`;
                }
                sel.innerHTML = html;
                // ensure select2 is applied so tagging works even when no options
                $(sel).select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    tags: true,
                    allowClear: true,
                    placeholder: 'Choose or type test name',
                    containerCss: {
                        'width': '100%'
                    }
                });
                return;
            }
            fetch(`ajax/visit-ajax.php?action=get_tests_by_type&type_id=${typeId}`)
                .then(r => r.json())
                .then(data => {
                    console.debug('tests loaded for type', typeId, data);
                    let html = '<option value="">Select or type name</option>';
                    data.forEach(function(test) {
                        html += `<option value="${test.name}"${test.name === selectedName ? ' selected' : ''}>${test.name}</option>`;
                    });
                    sel.innerHTML = html;
                    // re‑apply select2 so options refresh
                    $(sel).select2({
                        theme: 'bootstrap-5',
                        width: '100%',
                        tags: true,
                        allowClear: true,
                        placeholder: 'Choose or type test name',
                        containerCss: {
                            'width': '100%'
                        }
                    });
                })
                .catch(err => console.error('error fetching tests by type', err));
        }

        // Add Test Row
        document.getElementById('addTestBtn').addEventListener('click', function() {
            const container = document.getElementById('testsContainer');
            const rowKey = testIndex;
            const html = `
            <div class="test-row card mb-3" style="background-color: #e3f2fd; border-left: 4px solid #2196F3;" data-row="${rowKey}">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Test Type</label>
                            <select class="form-select form-select-sm test-type-select" name="tests[${rowKey}][test_type]" data-row="${rowKey}">
                                ${renderTypeOptions()}
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Test Name</label>
                            <select class="form-select form-select-sm test-name-select" name="tests[${rowKey}][test_name]">
                                <option value="">Select or type name</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Urgency</label>
                            <select class="form-select form-select-sm" name="tests[${rowKey}][urgency]">
                                <option value="Routine">Routine</option>
                                <option value="Urgent">Urgent</option>
                                <option value="STAT">STAT</option>
                            </select>
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="button" class="btn btn-sm btn-outline-danger w-100 remove-test" title="Remove test" onclick="if(confirm('Delete this test?')) { this.closest('.test-row').remove(); }">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
            // insert at top so new tests appear first
            container.insertAdjacentHTML('afterbegin', html);
            // initialize select2 on the newly added name dropdown as well
            setTimeout(() => {
                const newSel = container.querySelector(`[name="tests[${rowKey}][test_name]"]`);
                if (newSel) {
                    $(newSel).select2({
                        theme: 'bootstrap-5',
                        width: '100%',
                        tags: true,
                        allowClear: true,
                        placeholder: 'Choose or type test name',
                        containerCss: {
                            'width': '100%'
                        }
                    });
                }
            }, 100);
            testIndex++;
        });

        // Add Medicine Row with rotating background colors
        document.getElementById('addMedicineBtn').addEventListener('click', function() {
            const container = document.getElementById('medicinesContainer');
            // Get color index based on number of existing medicine rows (cycling through colors)
            const medicineCount = document.querySelectorAll('.medicine-row').length;
            const bgColor = medicineColors[medicineCount % medicineColors.length];

            // Build medicine options HTML from loaded medicines
            let medicineOptionsHtml = '<option value="">Select Medicine</option>';
            if (medicinesList && medicinesList.length > 0) {
                medicinesList.forEach(function(med) {
                    const label = med.label || med.medicine_name;
                    medicineOptionsHtml += `<option value="${med.medicine_name}">${label}</option>`;
                });
            }

            const html = `
            <div class="medicine-row card mb-3" style="background-color: ${bgColor}; border-left: 4px solid #9C27B0;">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Medicine Name</label>
                            <select class="form-select form-select-sm medicine-select" name="medicines[${medicineIndex}][medicine_name]">
                                ${medicineOptionsHtml}
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">Dose</label>
                            <input type="text" class="form-control form-control-sm" name="medicines[${medicineIndex}][dose]" placeholder="e.g., 500mg">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">Route</label>
                            <select class="form-select form-select-sm" name="medicines[${medicineIndex}][route]">
                                <option value="Oral">Oral</option>
                                <option value="IV">IV</option>
                                <option value="IM">IM</option>
                                <option value="SC">SC</option>
                                <option value="Topical">Topical</option>
                                <option value="Inhalation">Inhalation</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">Frequency</label>
                            <select class="form-select form-select-sm" name="medicines[${medicineIndex}][frequency]">
                                <option value="">-- Select --</option>
                                <option value="Once Daily">Once Daily</option>
                                <option value="Twice Daily">Twice Daily</option>
                                <option value="Thrice Daily">Thrice Daily</option>
                                <option value="Every 4 Hours">Every 4 Hours</option>
                                <option value="Every 6 Hours">Every 6 Hours</option>
                                <option value="Every 8 Hours">Every 8 Hours</option>
                                <option value="As Needed">As Needed</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label small fw-bold">Days</label>
                            <input type="number" class="form-control form-control-sm" name="medicines[${medicineIndex}][duration_days]" placeholder="7" min="1" max="365">
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="button" class="btn btn-sm btn-outline-danger w-100 remove-medicine" title="Remove medicine" onclick="if(confirm('Delete this medicine?')) { this.closest('.medicine-row').remove(); }">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Instructions</label>
                            <input type="text" class="form-control form-control-sm" name="medicines[${medicineIndex}][instructions]" placeholder="e.g., After meals, with water">
                        </div>
                    </div>
                </div>
            </div>
        `;
            container.insertAdjacentHTML('afterbegin', html);

            // Initialize Select2 autocomplete on the new medicine select
            setTimeout(() => {
                const sel = container.querySelector(`[name="medicines[${medicineIndex}][medicine_name]"]`);
                if (sel) initMedicineSelect(sel);
            }, 100);

            medicineIndex++;
        });

        // Remove Test Row (also mark saved tests for deletion)
        document.addEventListener('click', function(e) {
            if (e.target.closest('.remove-test')) {
                const row = e.target.closest('.test-row');
                // if this is an existing saved test, record its id for server-side deletion
                const rowKey = row.getAttribute('data-row');
                if (rowKey && rowKey.startsWith('saved_')) {
                    const id = rowKey.replace('saved_', '');
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'tests_to_delete[]';
                    hidden.value = id;
                    document.getElementById('visitForm').appendChild(hidden);
                }
                if (document.querySelectorAll('.test-row').length > 1) {
                    row.remove();
                }
            }
        });

// Remove Medicine Row (mark saved ones for deletion)
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-medicine')) {
            const row = e.target.closest('.medicine-row');
            // if this is a saved entry, queue it for deletion
            const savedKey = row.getAttribute('data-row');
            if (savedKey && savedKey.startsWith('saved_')) {
                const id = savedKey.replace('saved_', '');
                const hid = document.createElement('input');
                hid.type = 'hidden';
                hid.name = 'medicines_to_delete[]';
                hid.value = id;
                // use dedicated container so we can inspect easily
                const deleteContainer = document.getElementById('medicinesToDeleteContainer');
                if (deleteContainer) {
                    deleteContainer.appendChild(hid);
                }
            }
            if (document.querySelectorAll('.medicine-row').length > 1) {
                row.remove();
            }
        }
    });

        // when the type dropdown changes, fetch available tests and update the name dropdown
        document.addEventListener('change', function(e) {
            const typeEl = e.target.closest('.test-type-select');
            if (typeEl) {
                const rowKey = typeEl.getAttribute('data-row');
                const typeId = typeEl.value;
                console.debug('type changed', {
                    rowKey,
                    typeId
                });
                populateNamesForRow(rowKey, typeId, '');
            }
        });

        // populate any existing saved rows with correct name options on load
        document.querySelectorAll('.test-type-select').forEach(function(selectEl) {
            const rowKey = selectEl.getAttribute('data-row');
            const typeId = selectEl.value;
            // if there is already a chosen name, preserve it
            const nameEl = document.querySelector(`.test-row[data-row="${rowKey}"] .test-name-select`);
            const selectedName = nameEl ? nameEl.value : '';
            if (typeId) {
                populateNamesForRow(rowKey, typeId, selectedName);
            }
        });

        // Reinitialize Select2 when Prescription tab is clicked
        const prescriptionTab = document.getElementById('prescription-tab');
        if (prescriptionTab) {
            prescriptionTab.addEventListener('shown.bs.tab', function() {
                setTimeout(() => {
                    $('.medicine-select').each(function() {
                        if (!$(this).hasClass('select2-hidden-accessible')) {
                            $(this).select2({
                                theme: 'bootstrap-5',
                                width: '100%',
                                allowClear: true,
                                placeholder: 'Search and select medicine...',
                                containerCss: {
                                    'width': '100%'
                                }
                            });
                        }
                    });
                }, 100);
            });
        }
    });
</script>

<style>
    /* Modern UI Improvements */
    .page-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem;
        border-radius: 8px;
        margin-bottom: 2rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .page-header h1 {
        margin: 0;
        font-weight: 700;
    }

    /* Card styling */
    .card {
        border: none;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
        transition: box-shadow 0.3s ease;
    }

    /* vertical pills menu look and feel */
    .nav-pills .nav-link {
        border-radius: 0.375rem;
        margin-bottom: 0.5rem;
        font-weight: 500;
    }

    .nav-pills .nav-link.active {
        background-color: #667eea;
        color: #fff;
    }

    /* ensure content area stands out */
    .tab-content {
        background: #ffffff;
        border-radius: 0 0 8px 8px;
    }


    .card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
    }

    .card-header {
        background-color: #f8f9fa;
        border-bottom: 2px solid #e9ecef;
        border-radius: 8px 8px 0 0;
    }

    /* Tab styling */
    .nav-tabs {
        border-bottom: 2px solid #dee2e6;
    }

    .nav-tabs .nav-link {
        color: #666;
        border: none;
        border-bottom: 3px solid transparent;
        font-weight: 500;
        transition: all 0.3s ease;
        padding: 0.75rem 1.25rem;
    }

    .nav-tabs .nav-link:hover {
        color: #667eea;
        background-color: #f8f9fa;
        border-bottom-color: rgba(102, 126, 234, 0.3);
    }

    .nav-tabs .nav-link.active {
        color: #667eea;
        background-color: transparent;
        border-bottom-color: #667eea;
    }

    /* Form controls */
    .form-label {
        color: #333;
        font-weight: 500;
        margin-bottom: 0.5rem;
    }

    .form-control,
    .form-select {
        border: 1px solid #dee2e6;
        border-radius: 6px;
        transition: border-color 0.3s ease, box-shadow 0.3s ease;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }

    /* Test and Medicine rows */
    .test-row,
    .medicine-row {
        transition: all 0.3s ease;
    }

    .test-row.card {
        border-left: 4px solid #2196F3;
    }

    .medicine-row.card {
        border-left: 4px solid #9C27B0;
    }

    /* Badges */
    .badge {
        padding: 0.4rem 0.8rem;
        border-radius: 12px;
        font-size: 0.85rem;
        font-weight: 600;
    }

    /* Vital signs indicators */
    .vital-sign-status {
        font-size: 0.8rem;
        font-weight: 600;
        display: block;
        margin-top: 0.25rem;
    }

    .status-normal {
        color: #28a745;
    }

    .status-warning {
        color: #ffc107;
    }

    .status-danger {
        color: #dc3545;
    }

    /* Button styling */
    .btn {
        border-radius: 6px;
        font-weight: 600;
        transition: all 0.3s ease;
        text-transform: none;
    }

    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }

    .btn-success {
        background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
        border: none;
    }

    .btn-success:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(168, 224, 99, 0.4);
    }

    .btn-secondary {
        background-color: #6c757d;
        border: none;
    }

    .btn-secondary:hover {
        background-color: #5a6268;
        transform: translateY(-2px);
    }

    /* Alert styling */
    .alert {
        border-radius: 8px;
        border: none;
    }

    .alert-info {
        background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
        color: white;
    }

    .alert-warning {
        background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
        color: white;
    }

    .alert-danger {
        background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        color: white;
    }

    .alert-success {
        background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
        color: white;
    }

    .alert-dismissible .btn-close {
        opacity: 0.8;
    }

    .alert-dismissible .btn-close:hover {
        opacity: 1;
    }

    /* Patient info banner */
    .bg-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    }

    .btn-outline-light {
        border: 2px solid rgba(255, 255, 255, 0.5);
        color: white;
    }

    .btn-outline-light:hover {
        background-color: rgba(255, 255, 255, 0.2);
        border-color: white;
    }

    /* Tab pane styling */
    .tab-pane {
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    /* BMI Display */
    .bmi-display {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1rem;
        border-radius: 6px;
        font-size: 1.25rem;
        font-weight: 700;
        text-align: center;
    }

    /* Action buttons container */
    .card-body .btn-lg {
        padding: 0.75rem 1.5rem;
    }

    /* Prescription notes special styling */
    .card[style*="background-color: #fff3cd"] {
        border: 2px solid #ffc107 !important;
        background-color: #fffbf0 !important;
    }

    /* Responsive improvements */
    @media (max-width: 768px) {
        .page-header {
            padding: 1.5rem 1rem;
        }

        .nav-tabs .nav-link {
            padding: 0.5rem 0.75rem;
            font-size: 0.9rem;
        }

        .btn-lg {
            width: 100%;
            margin-bottom: 0.5rem;
        }
    }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>