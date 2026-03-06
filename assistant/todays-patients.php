<?php
/**
 * Today's Registered Patients
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

$pageTitle = "Today's Patients";

require_once __DIR__ . '/../includes/auth.php';
requireAssistant();

require_once __DIR__ . '/../classes/Patient.php';
require_once __DIR__ . '/../classes/Session.php';

$patient = new Patient();
$clinicSession = new ClinicSession();

$todaysPatients = $patient->getTodaysPatients();
$activeSessions = $clinicSession->getActiveSessions();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h1><i class="bi bi-list-check me-2"></i>Today's Registered Patients</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Today's Patients</li>
        </ol>
    </nav>
</div>

<?php displayFlash(); ?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-people me-2"></i>
            Patients Registered Today (<?php echo count($todaysPatients); ?>)
        </span>
        <a href="register-patient.php" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i>Register New
        </a>
    </div>
    <div class="card-body p-0">
        <?php if (empty($todaysPatients)): ?>
            <div class="text-center py-5">
                <i class="bi bi-person-x display-4 text-muted"></i>
                <p class="text-muted mt-2">No patients registered today</p>
                <a href="register-patient.php" class="btn btn-success">
                    <i class="bi bi-person-plus me-2"></i>Register First Patient
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Patient Code</th>
                            <th>Name</th>
                            <th>Age/Gender</th>
                            <th>Phone</th>
                            <th>Blood Group</th>
                            <th>Registration Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($todaysPatients as $pt): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($pt['patient_code']); ?></code></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($pt['full_name']); ?></strong>
                                    <?php if (!empty($pt['nic_number'])): ?>
                                        <br><small class="text-muted">NIC: <?php echo htmlspecialchars($pt['nic_number']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $pt['age']; ?>y / <?php echo $pt['gender']; ?></td>
                                <td><?php echo htmlspecialchars($pt['phone']); ?></td>
                                <td>
                                    <span class="badge bg-secondary"><?php echo $pt['blood_group']; ?></span>
                                </td>
                                <td><?php echo formatDateTime($pt['created_at'], 'h:i A'); ?></td>
                                <td>
                                    <?php if (!empty($activeSessions)): ?>
                                        <a href="add-to-queue.php?patient_id=<?php echo $pt['patient_id']; ?>" 
                                           class="btn btn-sm btn-primary" title="Add to Queue">
                                            <i class="bi bi-plus-circle"></i> Queue
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">No active session</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>