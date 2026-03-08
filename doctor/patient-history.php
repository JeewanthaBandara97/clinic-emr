<?php
/**
 * Patient Medical History
 * Clinic EMR System
 */

require_once __DIR__ . '/../includes/auth.php';
requireDoctor();
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';   // ADD THIS 

require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Patient.php';
require_once __DIR__ . '/../classes/Visit.php';
require_once __DIR__ . '/../classes/Prescription.php';
require_once __DIR__ . '/../classes/Test.php';

$patientId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$patientId) {
    redirect(APP_URL . '/doctor/search-patient.php', 'danger', 'Invalid patient ID.');
}

$patientObj = new Patient();
$visitObj = new Visit();
$prescriptionObj = new Prescription();
$testObj = new Test();

$patient = $patientObj->getById($patientId);

if (!$patient) {
    redirect(APP_URL . '/doctor/search-patient.php', 'danger', 'Patient not found.');
}

$visits = $visitObj->getByPatientId($patientId, 50);
$prescriptions = $prescriptionObj->getByPatientId($patientId, 20);
$tests = $testObj->getByPatientId($patientId, 20);

// Get vital signs
$db = Database::getInstance();
$vitalSigns = $db->fetchAll(
    "SELECT vs.*, v.visit_code, v.visit_date, v.visit_time
     FROM vital_signs vs
     JOIN visits v ON vs.visit_id = v.visit_id
     WHERE vs.patient_id = ?
     ORDER BY v.visit_date DESC, v.visit_time DESC
     LIMIT 20",
    [$patientId]
);

$pageTitle = $patient['full_name'] . ' - Medical History';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h1><i class="bi bi-clock-history me-2"></i>Medical History</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="patient-profile.php?id=<?php echo $patientId; ?>"><?php echo htmlspecialchars($patient['full_name']); ?></a></li>
            <li class="breadcrumb-item active">Medical History</li>
        </ol>
    </nav>
</div>

<!-- Patient Summary -->
<div class="card mb-4 bg-primary text-white">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h4 class="mb-1"><?php echo htmlspecialchars($patient['full_name']); ?></h4>
                <p class="mb-0">
                    <span class="me-3"><i class="bi bi-upc me-1"></i><?php echo $patient['patient_code']; ?></span>
                    <span class="me-3"><i class="bi bi-calendar3 me-1"></i><?php echo $patient['age']; ?> years</span>
                    <span class="me-3"><i class="bi bi-gender-<?php echo strtolower($patient['gender']) === 'male' ? 'male' : 'female'; ?> me-1"></i><?php echo $patient['gender']; ?></span>
                    <span><i class="bi bi-droplet me-1"></i><?php echo $patient['blood_group']; ?></span>
                </p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="create-visit.php?patient_id=<?php echo $patientId; ?>" class="btn btn-light">
                    <i class="bi bi-plus-circle me-2"></i>New Visit
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs" id="historyTabs" role="tablist">
    <li class="nav-item">
        <a class="nav-link active" id="visits-tab" data-bs-toggle="tab" href="#visits" role="tab">
            <i class="bi bi-calendar-check me-1"></i>Visits (<?php echo count($visits); ?>)
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="vital-signs-tab" data-bs-toggle="tab" href="#vital-signs" role="tab">
            <i class="bi bi-activity me-1"></i>Vital Signs (<?php echo count($vitalSigns); ?>)
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="prescriptions-tab" data-bs-toggle="tab" href="#prescriptions" role="tab">
            <i class="bi bi-file-medical me-1"></i>Prescriptions (<?php echo count($prescriptions); ?>)
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="tests-tab" data-bs-toggle="tab" href="#tests" role="tab">
            <i class="bi bi-clipboard2-pulse me-1"></i>Tests (<?php echo count($tests); ?>)
        </a>
    </li>
</ul>

<div class="tab-content" id="historyTabsContent">
    <!-- Visits Tab -->
    <div class="tab-pane fade show active" id="visits" role="tabpanel">
        <div class="card border-top-0 rounded-top-0">
            <div class="card-body p-0">
                <?php if (empty($visits)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-calendar-x display-4 text-muted"></i>
                        <p class="text-muted mt-2">No visit records found</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($visits as $visit): ?>
                        <div class="border-bottom p-4">
                            <div class="row">
                                <div class="col-md-3">
                                    <h6 class="text-primary mb-1"><?php echo formatDate($visit['visit_date']); ?></h6>
                                    <small class="text-muted">
                                        <?php echo formatTime($visit['visit_time']); ?><br>
                                        <code><?php echo $visit['visit_code']; ?></code>
                                    </small>
                                </div>
                                <div class="col-md-7">
                                    <p class="mb-1"><strong>Doctor:</strong> <?php echo htmlspecialchars($visit['doctor_name']); ?></p>
                                    <?php if ($visit['symptoms']): ?>
                                        <p class="mb-1"><strong>Symptoms:</strong> <?php echo htmlspecialchars($visit['symptoms']); ?></p>
                                    <?php endif; ?>
                                    <?php if ($visit['diagnosis']): ?>
                                        <p class="mb-1"><strong>Diagnosis:</strong> <?php echo htmlspecialchars($visit['diagnosis']); ?></p>
                                    <?php endif; ?>
                                    <?php if ($visit['notes']): ?>
                                        <p class="mb-0 text-muted"><small><?php echo htmlspecialchars($visit['notes']); ?></small></p>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-2 text-end">
                                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $visit['status'])); ?>">
                                        <?php echo $visit['status']; ?>
                                    </span>
                                    <div class="mt-2">
                                        <a href="visit-prescriptions.php?visit_id=<?php echo $visit['visit_id']; ?>" 
                                           class="btn btn-sm btn-outline-primary" title="View prescriptions for this visit">
                                            <i class="bi bi-capsule me-1"></i>Rx
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Vital Signs Tab -->
    <div class="tab-pane fade" id="vital-signs" role="tabpanel">
        <div class="card border-top-0 rounded-top-0">
            <div class="card-body p-0">
                <?php if (empty($vitalSigns)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-activity display-4 text-muted"></i>
                        <p class="text-muted mt-2">No vital signs recorded</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 table-sm">
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Visit Code</th>
                                    <th>Temperature</th>
                                    <th>BP (Systolic/Diastolic)</th>
                                    <th>Pulse</th>
                                    <th>Weight</th>
                                    <th>Height</th>
                                    <th>BMI</th>
                                    <th>SpO₂</th>
                                    <th>RR</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vitalSigns as $vital): ?>
                                    <tr>
                                        <td>
                                            <small><?php echo formatDateTime($vital['visit_date'] . ' ' . $vital['visit_time']); ?></small>
                                        </td>
                                        <td>
                                            <code><?php echo $vital['visit_code']; ?></code>
                                        </td>
                                        <td>
                                            <?php if ($vital['temperature']): ?>
                                                <span class="<?php echo $vital['temperature'] > 38 ? 'text-danger' : ($vital['temperature'] < 36 ? 'text-warning' : ''); ?>">
                                                    <?php echo number_format($vital['temperature'], 1); ?>°C
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($vital['blood_pressure_systolic']): ?>
                                                <?php echo $vital['blood_pressure_systolic']; ?>/<?php echo $vital['blood_pressure_diastolic'] ?? '-'; ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo $vital['pulse_rate'] ?? '-'; ?>
                                        </td>
                                        <td>
                                            <?php echo $vital['weight'] ? number_format($vital['weight'], 2) . ' kg' : '-'; ?>
                                        </td>
                                        <td>
                                            <?php echo $vital['height'] ? number_format($vital['height'], 2) . ' cm' : '-'; ?>
                                        </td>
                                        <td>
                                            <?php if ($vital['bmi']): ?>
                                                <span class="<?php 
                                                    $bmi = $vital['bmi'];
                                                    echo $bmi < 18.5 ? 'text-info' : ($bmi < 25 ? 'text-success' : ($bmi < 30 ? 'text-warning' : 'text-danger'));
                                                ?>">
                                                    <?php echo number_format($vital['bmi'], 1); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($vital['oxygen_saturation']): ?>
                                                <span class="<?php echo $vital['oxygen_saturation'] < 95 ? 'text-danger' : 'text-success'; ?>">
                                                    <?php echo $vital['oxygen_saturation']; ?>%
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo $vital['respiratory_rate'] ?? '-'; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Prescriptions Tab -->
    <div class="tab-pane fade" id="prescriptions" role="tabpanel">
        <div class="card border-top-0 rounded-top-0">
            <div class="card-body p-0">
                <?php if (empty($prescriptions)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-file-medical display-4 text-muted"></i>
                        <p class="text-muted mt-2">No prescriptions found</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Prescription Code</th>
                                    <th>Doctor</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($prescriptions as $rx): ?>
                                    <tr>
                                        <td><?php echo formatDate($rx['prescription_date']); ?></td>
                                        <td><code><?php echo $rx['prescription_code']; ?></code></td>
                                        <td><?php echo htmlspecialchars($rx['doctor_name']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $rx['status'] === 'Active' ? 'success' : 'secondary'; ?>">
                                                <?php echo $rx['status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="print-prescription.php?id=<?php echo $rx['prescription_id']; ?>" 
                                               class="btn btn-sm btn-outline-primary" target="_blank">
                                                <i class="bi bi-printer"></i> Print
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Tests Tab -->
    <div class="tab-pane fade" id="tests" role="tabpanel">
        <div class="card border-top-0 rounded-top-0">
            <div class="card-body p-0">
                <?php if (empty($tests)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-clipboard2-pulse display-4 text-muted"></i>
                        <p class="text-muted mt-2">No test records found</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Test Name</th>
                                    <th>Type</th>
                                    <th>Urgency</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tests as $test): ?>
                                    <tr>
                                        <td><?php echo formatDate($test['visit_date']); ?></td>
                                        <td><?php echo htmlspecialchars($test['test_name']); ?></td>
                                        <td><span class="badge bg-info"><?php echo $test['test_type']; ?></span></td>
                                        <td>
                                            <span class="badge bg-<?php echo $test['urgency'] === 'STAT' ? 'danger' : ($test['urgency'] === 'Urgent' ? 'warning' : 'secondary'); ?>">
                                                <?php echo $test['urgency']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $test['status'] === 'Completed' ? 'success' : 'primary'; ?>">
                                                <?php echo $test['status']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>