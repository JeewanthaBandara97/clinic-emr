<?php
/**
 * Patient Profile
 * Clinic EMR System
 */

require_once __DIR__ . '/../includes/auth.php';
requireDoctor();

require_once __DIR__ . '/../classes/Patient.php';
require_once __DIR__ . '/../classes/Visit.php';
require_once __DIR__ . '/../classes/Prescription.php';

$patientId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$patientId) {
    redirect(APP_URL . '/doctor/search-patient.php', 'danger', 'Invalid patient ID.');
}

$patientObj = new Patient();
$visitObj = new Visit();
$prescriptionObj = new Prescription();

$patient = $patientObj->getById($patientId);

if (!$patient) {
    redirect(APP_URL . '/doctor/search-patient.php', 'danger', 'Patient not found.');
}

$recentVisits = $visitObj->getByPatientId($patientId, 5);
$recentPrescriptions = $prescriptionObj->getByPatientId($patientId, 5);
$vitalHistory = $visitObj->getVitalSignHistory($patientId, 5);

$pageTitle = $patient['full_name'] . ' - Patient Profile';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h1><i class="bi bi-person-circle me-2"></i>Patient Profile</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="search-patient.php">Search Patient</a></li>
            <li class="breadcrumb-item active"><?php echo htmlspecialchars($patient['full_name']); ?></li>
        </ol>
    </nav>
</div>

<div class="row">
    <!-- Patient Info Card -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="patient-info-card">
                <div class="text-center mb-3">
                    <div class="avatar-large mx-auto mb-2">
                        <?php echo strtoupper(substr($patient['first_name'], 0, 1) . substr($patient['last_name'], 0, 1)); ?>
                    </div>
                    <h4 class="patient-name mb-0"><?php echo htmlspecialchars($patient['full_name']); ?></h4>
                    <p class="patient-code mb-0"><?php echo htmlspecialchars($patient['patient_code']); ?></p>
                </div>
                
                <div class="patient-details">
                    <div class="detail-item">
                        <i class="bi bi-calendar3"></i>
                        <span><?php echo $patient['age']; ?> years old (<?php echo formatDate($patient['date_of_birth']); ?>)</span>
                    </div>
                    <div class="detail-item">
                        <i class="bi bi-gender-<?php echo strtolower($patient['gender']) === 'male' ? 'male' : 'female'; ?>"></i>
                        <span><?php echo $patient['gender']; ?></span>
                    </div>
                    <div class="detail-item">
                        <i class="bi bi-telephone"></i>
                        <span><?php echo htmlspecialchars($patient['phone']); ?></span>
                    </div>
                    <div class="detail-item">
                        <i class="bi bi-droplet-fill"></i>
                        <span>Blood Group: <?php echo $patient['blood_group']; ?></span>
                    </div>
                </div>
            </div>
            
            <div class="card-body">
                <h6 class="text-muted mb-3">Contact Information</h6>
                
                <?php if ($patient['address']): ?>
                    <p class="mb-2">
                        <i class="bi bi-geo-alt text-muted me-2"></i>
                        <?php echo nl2br(htmlspecialchars($patient['address'])); ?>
                    </p>
                <?php endif; ?>
                
                <?php if ($patient['emergency_contact_name']): ?>
                    <hr>
                    <h6 class="text-muted mb-2">Emergency Contact</h6>
                    <p class="mb-1"><strong><?php echo htmlspecialchars($patient['emergency_contact_name']); ?></strong></p>
                    <p class="mb-0"><i class="bi bi-telephone me-2"></i><?php echo htmlspecialchars($patient['emergency_contact_phone']); ?></p>
                <?php endif; ?>
                
                <hr>
                <div class="d-grid gap-2">
                    <a href="create-visit.php?patient_id=<?php echo $patientId; ?>" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>New Visit
                    </a>
                    <a href="patient-history.php?id=<?php echo $patientId; ?>" class="btn btn-outline-primary">
                        <i class="bi bi-clock-history me-2"></i>Full History
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Medical Info -->
    <div class="col-lg-8 mb-4">
        <!-- Medical Conditions -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-heart-pulse me-2"></i>Medical Information
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <h6 class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i>Allergies</h6>
                        <p class="mb-0"><?php echo $patient['allergies'] ? nl2br(htmlspecialchars($patient['allergies'])) : '<span class="text-muted">None recorded</span>'; ?></p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <h6 class="text-warning"><i class="bi bi-bandaid me-1"></i>Chronic Diseases</h6>
                        <p class="mb-0"><?php echo $patient['chronic_diseases'] ? nl2br(htmlspecialchars($patient['chronic_diseases'])) : '<span class="text-muted">None recorded</span>'; ?></p>
                    </div>
                </div>
                
                <?php if ($patient['weight'] || $patient['height']): ?>
                    <hr>
                    <div class="row">
                        <div class="col-md-4">
                            <h6 class="text-muted">Weight</h6>
                            <p class="h4 mb-0"><?php echo $patient['weight'] ? $patient['weight'] . ' kg' : '-'; ?></p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-muted">Height</h6>
                            <p class="h4 mb-0"><?php echo $patient['height'] ? $patient['height'] . ' cm' : '-'; ?></p>
                        </div>
                        <?php if ($patient['weight'] && $patient['height']): ?>
                            <?php $bmi = calculateBMI($patient['weight'], $patient['height']); ?>
                            <div class="col-md-4">
                                <h6 class="text-muted">BMI</h6>
                                <p class="h4 mb-0"><?php echo $bmi; ?> <small class="text-muted">(<?php echo getBMICategory($bmi); ?>)</small></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Recent Visits -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-calendar-check me-2"></i>Recent Visits</span>
                <a href="patient-history.php?id=<?php echo $patientId; ?>" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentVisits)): ?>
                    <div class="text-center py-4">
                        <p class="text-muted mb-0">No visit records found</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Doctor</th>
                                    <th>Diagnosis</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentVisits as $visit): ?>
                                    <tr>
                                        <td><?php echo formatDate($visit['visit_date']); ?></td>
                                        <td><?php echo htmlspecialchars($visit['doctor_name']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($visit['diagnosis'] ?? '-', 0, 40)); ?>...</td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $visit['status'])); ?>">
                                                <?php echo $visit['status']; ?>
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
        
        <!-- Vital Signs History -->
        <?php if (!empty($vitalHistory)): ?>
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-activity me-2"></i>Vital Signs History
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>BP</th>
                                    <th>Pulse</th>
                                    <th>Temp</th>
                                    <th>Weight</th>
                                    <th>SpO2</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vitalHistory as $vital): ?>
                                    <tr>
                                        <td><?php echo formatDate($vital['visit_date']); ?></td>
                                        <td>
                                            <?php 
                                            if ($vital['blood_pressure_systolic'] && $vital['blood_pressure_diastolic']) {
                                                echo $vital['blood_pressure_systolic'] . '/' . $vital['blood_pressure_diastolic'];
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo $vital['pulse_rate'] ?? '-'; ?></td>
                                        <td><?php echo $vital['temperature'] ? $vital['temperature'] . '°C' : '-'; ?></td>
                                        <td><?php echo $vital['weight'] ? $vital['weight'] . 'kg' : '-'; ?></td>
                                        <td><?php echo $vital['oxygen_saturation'] ? $vital['oxygen_saturation'] . '%' : '-'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.avatar-large {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    font-weight: 600;
    color: #fff;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>