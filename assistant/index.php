<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>

<?php
/**
 * Assistant Dashboard
 */

$pageTitle = 'Assistant Dashboard';

require_once __DIR__ . '/../includes/auth.php';
requireAssistant();
 
require_once __DIR__ . '/../classes/Session.php';
require_once __DIR__ . '/../classes/Patient.php';

$clinicSession = new ClinicSession();
$patient = new Patient();

$stats = $clinicSession->getTodayStats();
$todaySessions = $clinicSession->getTodaySessions();
$todaysPatients = $patient->getTodaysPatients();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h1><i class="bi bi-speedometer2 me-2"></i>Assistant Dashboard</h1>
</div>

<?php displayFlash(); ?>

<!-- Statistics -->
<div class="row mb-4">
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="stat-card">
            <div class="stat-icon primary"><i class="bi bi-calendar-check"></i></div>
            <div class="stat-value"><?php echo $stats['total_sessions'] ?? 0; ?></div>
            <div class="stat-label">Today's Sessions</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="stat-card">
            <div class="stat-icon success"><i class="bi bi-person-plus"></i></div>
            <div class="stat-value"><?php echo $stats['new_patients'] ?? 0; ?></div>
            <div class="stat-label">New Patients Today</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="stat-card">
            <div class="stat-icon warning"><i class="bi bi-hourglass-split"></i></div>
            <div class="stat-value"><?php echo $stats['waiting_patients'] ?? 0; ?></div>
            <div class="stat-label">Patients Waiting</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="stat-card">
            <div class="stat-icon info"><i class="bi bi-check-circle"></i></div>
            <div class="stat-value"><?php echo $stats['completed_patients'] ?? 0; ?></div>
            <div class="stat-label">Completed Today</div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card mb-4">
    <div class="card-header"><i class="bi bi-lightning me-2"></i>Quick Actions</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <a href="create-session.php" class="btn btn-primary btn-lg w-100">
                    <i class="bi bi-calendar-plus me-2"></i>Create Session
                </a>
            </div>
            <div class="col-md-4">
                <a href="register-patient.php" class="btn btn-success btn-lg w-100">
                    <i class="bi bi-person-plus me-2"></i>Register Patient
                </a>
            </div>
            <div class="col-md-4">
                <a href="add-to-queue.php" class="btn btn-info btn-lg w-100 text-white">
                    <i class="bi bi-people me-2"></i>Add to Queue
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Today's Sessions -->
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-calendar3 me-2"></i>Today's Sessions</span>
                <a href="create-session.php" class="btn btn-sm btn-primary"><i class="bi bi-plus"></i> New</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($todaySessions)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-calendar-x display-4 text-muted"></i>
                        <p class="text-muted mt-2">No sessions created for today</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Session</th>
                                    <th>Doctor</th>
                                    <th>Time</th>
                                    <th>Patients</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($todaySessions as $session): ?>
                                    <tr>
                                        <td><code><?php echo htmlspecialchars($session['session_code']); ?></code></td>
                                        <td><?php echo htmlspecialchars($session['doctor_name']); ?></td>
                                        <td><?php echo formatTime($session['start_time']); ?></td>
                                        <td>
                                            <span class="badge bg-primary"><?php echo $session['total_patients']; ?></span>
                                            <?php if ($session['waiting_count'] > 0): ?>
                                                <span class="badge bg-warning"><?php echo $session['waiting_count']; ?> waiting</span>
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
    </div>
    
    <!-- Today's Registered Patients -->
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-people me-2"></i>Today's Registered Patients</span>
                <a href="todays-patients.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($todaysPatients)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-person-x display-4 text-muted"></i>
                        <p class="text-muted mt-2">No patients registered today</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Age/Gender</th>
                                    <th>Phone</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($todaysPatients, 0, 5) as $pt): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($pt['full_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo $pt['patient_code']; ?></small>
                                        </td>
                                        <td><?php echo $pt['age']; ?>y / <?php echo $pt['gender']; ?></td>
                                        <td><?php echo htmlspecialchars($pt['phone']); ?></td>
                                        <td>
                                            <a href="add-to-queue.php?patient_id=<?php echo $pt['patient_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-plus-circle"></i> Queue
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
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>