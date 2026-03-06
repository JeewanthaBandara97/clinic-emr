<?php
/**
 * Patient Queue
 * Clinic EMR System
 */

 
$pageTitle = 'Patient Queue';


require_once __DIR__ . '/../includes/auth.php';
requireDoctor();

require_once __DIR__ . '/../classes/Session.php';

$clinicSession = new ClinicSession();
$doctorId = User::getUserId();
$queue = $clinicSession->getDoctorQueue($doctorId);
$sessions = $clinicSession->getDoctorTodaySessions($doctorId);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h1><i class="bi bi-people-fill me-2"></i>Patient Queue</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Patient Queue</li>
        </ol>
    </nav>
</div>

<?php displayFlash(); ?>

<?php if (empty($sessions)): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>
        No sessions scheduled for today. Please contact the assistant to create a session.
    </div>
<?php else: ?>
    <!-- Session Tabs -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link active" href="#all" data-bs-toggle="tab">All Patients (<?php echo count($queue); ?>)</a>
        </li>
        <?php foreach ($sessions as $index => $session): ?>
            <li class="nav-item">
                <a class="nav-link" href="#session<?php echo $session['session_id']; ?>" data-bs-toggle="tab">
                    <?php echo htmlspecialchars($session['session_code']); ?> 
                    (<?php echo $session['total_patients']; ?>)
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
    
    <div class="card">
        <div class="card-body p-0">
            <?php if (empty($queue)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-4 text-muted"></i>
                    <p class="text-muted mt-2">No patients in queue</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th width="80">Queue #</th>
                                <th>Patient</th>
                                <th>Patient Code</th>
                                <th>Age</th>
                                <th>Gender</th>
                                <th>Phone</th>
                                <th>Check-in Time</th>
                                <th>Status</th>
                                <th width="150">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($queue as $patient): ?>
                                <tr class="<?php echo $patient['status'] === 'In Progress' ? 'table-warning' : ''; ?>">
                                    <td>
                                        <span class="queue-number"><?php echo $patient['queue_number']; ?></span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($patient['patient_name']); ?></strong>
                                    </td>
                                    <td><code><?php echo htmlspecialchars($patient['patient_code']); ?></code></td>
                                    <td><?php echo $patient['age']; ?>y</td>
                                    <td><?php echo $patient['gender']; ?></td>
                                    <td><?php echo htmlspecialchars($patient['phone']); ?></td>
                                    <td><?php echo formatDateTime($patient['check_in_time'], 'h:i A'); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $patient['status'])); ?>">
                                            <?php echo $patient['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($patient['status'] === 'Waiting'): ?>
                                            <a href="create-visit.php?session_id=<?php echo $patient['session_id']; ?>&patient_id=<?php echo $patient['patient_id']; ?>" 
                                               class="btn btn-sm btn-primary">
                                                <i class="bi bi-play-fill me-1"></i>Start Visit
                                            </a>
                                        <?php elseif ($patient['status'] === 'In Progress'): ?>
                                            <a href="create-visit.php?session_id=<?php echo $patient['session_id']; ?>&patient_id=<?php echo $patient['patient_id']; ?>" 
                                               class="btn btn-sm btn-success">
                                                <i class="bi bi-arrow-right-circle me-1"></i>Continue
                                            </a>
                                        <?php else: ?>
                                            <a href="patient-profile.php?id=<?php echo $patient['patient_id']; ?>" 
                                               class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-eye me-1"></i>View
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
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>