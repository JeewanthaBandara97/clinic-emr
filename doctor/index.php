<?php
/**
 * Doctor Dashboard
 * Clinic EMR System
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

 
$pageTitle = 'Doctor Dashboard';


require_once __DIR__ . '/../includes/auth.php';
requireDoctor();

require_once __DIR__ . '/../classes/Session.php';
require_once __DIR__ . '/../classes/Visit.php';

$clinicSession = new ClinicSession();
$visit = new Visit();

$doctorId = User::getUserId();
$todaySessions = $clinicSession->getDoctorTodaySessions($doctorId);
$queue = $clinicSession->getDoctorQueue($doctorId);
$todayVisits = $visit->getDoctorTodayVisits($doctorId);

// Calculate stats
$waitingCount = 0;
$inProgressCount = 0;
$completedCount = 0;

foreach ($queue as $q) {
    switch ($q['status']) {
        case 'Waiting': $waitingCount++; break;
        case 'In Progress': $inProgressCount++; break;
        case 'Completed': $completedCount++; break;
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h1><i class="bi bi-speedometer2 me-2"></i>Doctor Dashboard</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active">Dashboard</li>
        </ol>
    </nav>
</div>

<?php displayFlash(); ?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="bi bi-hourglass-split"></i>
            </div>
            <div class="stat-value"><?php echo $waitingCount; ?></div>
            <div class="stat-label">Patients Waiting</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="stat-card">
            <div class="stat-icon info">
                <i class="bi bi-person-check"></i>
            </div>
            <div class="stat-value"><?php echo $inProgressCount; ?></div>
            <div class="stat-label">In Progress</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="stat-card">
            <div class="stat-icon success">
                <i class="bi bi-check-circle"></i>
            </div>
            <div class="stat-value"><?php echo $completedCount; ?></div>
            <div class="stat-label">Completed Today</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="bi bi-calendar-check"></i>
            </div>
            <div class="stat-value"><?php echo count($todaySessions); ?></div>
            <div class="stat-label">Today's Sessions</div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Patient Queue -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-people-fill me-2"></i>Today's Patient Queue</span>
                <a href="patient-queue.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($queue)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-calendar-x display-4 text-muted"></i>
                        <p class="text-muted mt-2">No patients in queue today</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Queue</th>
                                    <th>Patient</th>
                                    <th>Age/Gender</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($queue, 0, 8) as $patient): ?>
                                    <tr class="<?php echo $patient['status'] === 'In Progress' ? 'table-info' : ''; ?>">
                                        <td>
                                            <span class="queue-number"><?php echo $patient['queue_number']; ?></span>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($patient['patient_name']); ?></strong>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($patient['patient_code']); ?></small>
                                        </td>
                                        <td><?php echo $patient['age']; ?>y / <?php echo $patient['gender']; ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $patient['status'])); ?>">
                                                <?php echo $patient['status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($patient['status'] === 'Waiting'): ?>
                                                <a href="create-visit.php?session_id=<?php echo $patient['session_id']; ?>&patient_id=<?php echo $patient['patient_id']; ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="bi bi-play-fill"></i> Start
                                                </a>
                                            <?php elseif ($patient['status'] === 'In Progress'): ?>
                                                <a href="create-visit.php?session_id=<?php echo $patient['session_id']; ?>&patient_id=<?php echo $patient['patient_id']; ?>" 
                                                   class="btn btn-sm btn-success">
                                                    <i class="bi bi-arrow-right-circle"></i> Continue
                                                </a>
                                            <?php else: ?>
                                                <a href="patient-profile.php?id=<?php echo $patient['patient_id']; ?>" 
                                                   class="btn btn-sm btn-outline-secondary">
                                                    <i class="bi bi-eye"></i> View
                                                </a>
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
    
    <!-- Quick Actions & Today's Sessions -->
    <div class="col-lg-4 mb-4">
        <!-- Quick Actions -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-lightning me-2"></i>Quick Actions
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="patient-queue.php" class="btn btn-primary">
                        <i class="bi bi-people-fill me-2"></i>View Queue
                    </a>
                    <a href="search-patient.php" class="btn btn-outline-primary">
                        <i class="bi bi-search me-2"></i>Search Patient
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Today's Sessions -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-calendar3 me-2"></i>Today's Sessions
            </div>
            <div class="card-body p-0">
                <?php if (empty($todaySessions)): ?>
                    <div class="text-center py-4">
                        <p class="text-muted mb-0">No sessions for today</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($todaySessions as $session): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo htmlspecialchars($session['session_code']); ?></strong>
                                        <br><small class="text-muted"><?php echo formatTime($session['start_time']); ?></small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-primary"><?php echo $session['total_patients']; ?> patients</span>
                                        <?php if ($session['waiting_count'] > 0): ?>
                                            <br><small class="text-warning"><?php echo $session['waiting_count']; ?> waiting</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Visits -->
<div class="card">
    <div class="card-header">
        <i class="bi bi-clock-history me-2"></i>Today's Completed Visits
    </div>
    <div class="card-body p-0">
        <?php if (empty($todayVisits)): ?>
            <div class="text-center py-4">
                <p class="text-muted mb-0">No completed visits today</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Visit Code</th>
                            <th>Patient</th>
                            <th>Time</th>
                            <th>Diagnosis</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($todayVisits, 0, 5) as $v): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($v['visit_code']); ?></code></td>
                                <td><?php echo htmlspecialchars($v['patient_name']); ?></td>
                                <td><?php echo formatTime($v['visit_time']); ?></td>
                                <td><?php echo htmlspecialchars(substr($v['diagnosis'] ?? '-', 0, 50)); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $v['status'])); ?>">
                                        <?php echo $v['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="patient-profile.php?id=<?php echo $v['patient_id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>