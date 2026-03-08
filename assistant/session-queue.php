<?php
/**
 * View Session Queue - Assistant Module
 * View and manage patients in a specific session
 */

$pageTitle = 'Session Queue';

require_once __DIR__ . '/../includes/auth.php';
requireAssistant();
require_once __DIR__ . '/../includes/functions.php';   // ADD THIS
require_once __DIR__ . '/../includes/csrf.php';   // ADD THIS
require_once __DIR__ . '/../classes/Session.php';
require_once __DIR__ . '/../classes/Patient.php';

$clinicSession = new ClinicSession();
$patientObj = new Patient();

// Get session ID from URL
$sessionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($sessionId <= 0) {
    redirect(APP_URL . '/assistant/sessions.php', 'danger', 'Invalid session ID.');
}

// Get session details
$session = $clinicSession->getById($sessionId);

if (!$session) {
    redirect(APP_URL . '/assistant/sessions.php', 'danger', 'Session not found.');
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    checkCSRF();
    
    $action = $_POST['action'];
    $patientId = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0;
    
    switch ($action) {
        case 'remove_patient':
            if ($patientId > 0) {
                $result = $clinicSession->removePatientFromQueue($sessionId, $patientId);
                if ($result) {
                    redirect(APP_URL . '/assistant/session-queue.php?id=' . $sessionId, 'success', 'Patient removed from queue successfully.');
                } else {
                    redirect(APP_URL . '/assistant/session-queue.php?id=' . $sessionId, 'danger', 'Failed to remove patient from queue.');
                }
            }
            break;
            
        case 'update_patient_status':
            $status = $_POST['status'] ?? '';
            $validStatuses = ['Waiting', 'In Progress', 'Completed', 'No Show', 'Cancelled'];
            
            if ($patientId > 0 && in_array($status, $validStatuses)) {
                $updated = $clinicSession->updatePatientStatus($sessionId, $patientId, $status);
                if ($updated) {
                    redirect(APP_URL . '/assistant/session-queue.php?id=' . $sessionId, 'success', 'Patient status updated to "' . $status . '".');
                } else {
                    redirect(APP_URL . '/assistant/session-queue.php?id=' . $sessionId, 'danger', 'Failed to update patient status.');
                }
            }
            break;
            
        case 'move_up':
            if ($patientId > 0) {
                $result = $clinicSession->movePatientInQueue($sessionId, $patientId, 'up');
                if ($result) {
                    redirect(APP_URL . '/assistant/session-queue.php?id=' . $sessionId, 'success', 'Patient moved up in queue.');
                }
            }
            break;
            
        case 'move_down':
            if ($patientId > 0) {
                $result = $clinicSession->movePatientInQueue($sessionId, $patientId, 'down');
                if ($result) {
                    redirect(APP_URL . '/assistant/session-queue.php?id=' . $sessionId, 'success', 'Patient moved down in queue.');
                }
            }
            break;
    }
}

// Get queue for this session
$queue = $clinicSession->getSessionQueue($sessionId);

// Calculate statistics
$stats = [
    'total' => count($queue),
    'waiting' => 0,
    'in_progress' => 0,
    'completed' => 0,
    'no_show' => 0,
    'cancelled' => 0
];

foreach ($queue as $patient) {
    switch ($patient['status']) {
        case 'Waiting': $stats['waiting']++; break;
        case 'In Progress': $stats['in_progress']++; break;
        case 'Completed': $stats['completed']++; break;
        case 'No Show': $stats['no_show']++; break;
        case 'Cancelled': $stats['cancelled']++; break;
    }
}

$pageTitle = 'Queue: ' . $session['session_code'];

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h1><i class="bi bi-people me-2"></i>Session Queue</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="sessions.php">Sessions</a></li>
            <li class="breadcrumb-item active"><?php echo htmlspecialchars($session['session_code']); ?></li>
        </ol>
    </nav>
</div>

<?php displayFlash(); ?>

<!-- Session Info Card -->
<div class="card mb-4 border-primary">
    <div class="card-header bg-primary text-white">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h5 class="mb-0">
                    <i class="bi bi-calendar-check me-2"></i>
                    Session: <?php echo htmlspecialchars($session['session_code']); ?>
                </h5>
            </div>
            <div class="col-md-4 text-md-end">
                <?php
                $statusBadgeClass = match($session['status']) {
                    'Scheduled' => 'bg-secondary',
                    'Active' => 'bg-success',
                    'Completed' => 'bg-info',
                    'Cancelled' => 'bg-danger',
                    default => 'bg-secondary'
                };
                ?>
                <span class="badge <?php echo $statusBadgeClass; ?> fs-6">
                    <?php echo $session['status']; ?>
                </span>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 mb-2 mb-md-0">
                <div class="d-flex align-items-center">
                    <i class="bi bi-calendar3 text-primary me-2 fs-4"></i>
                    <div>
                        <small class="text-muted d-block">Date</small>
                        <strong><?php echo formatDate($session['session_date']); ?></strong>
                        <?php if ($session['session_date'] === date('Y-m-d')): ?>
                            <span class="badge bg-info ms-1">Today</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-2 mb-md-0">
                <div class="d-flex align-items-center">
                    <i class="bi bi-clock text-success me-2 fs-4"></i>
                    <div>
                        <small class="text-muted d-block">Start Time</small>
                        <strong><?php echo formatTime($session['start_time']); ?></strong>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-2 mb-md-0">
                <div class="d-flex align-items-center">
                    <i class="bi bi-person-badge text-info me-2 fs-4"></i>
                    <div>
                        <small class="text-muted d-block">Doctor</small>
                        <strong><?php echo htmlspecialchars($session['doctor_name']); ?></strong>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="d-flex align-items-center">
                    <i class="bi bi-people text-warning me-2 fs-4"></i>
                    <div>
                        <small class="text-muted d-block">Max Capacity</small>
                        <strong><?php echo $session['max_patients']; ?> patients</strong>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (!empty($session['notes'])): ?>
            <hr>
            <div class="alert alert-light mb-0">
                <i class="bi bi-sticky me-2"></i>
                <strong>Notes:</strong> <?php echo htmlspecialchars($session['notes']); ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-footer bg-light">
        <div class="d-flex flex-wrap gap-2">
            <a href="add-to-queue.php?session_id=<?php echo $sessionId; ?>" class="btn btn-success">
                <i class="bi bi-person-plus me-1"></i>Add Patient to Queue
            </a>
            <a href="sessions.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back to Sessions
            </a>
            <button type="button" class="btn btn-outline-primary" onclick="window.print();">
                <i class="bi bi-printer me-1"></i>Print Queue
            </button>
        </div>
    </div>
</div>

<!-- Queue Statistics -->
<div class="row mb-4">
    <div class="col-6 col-md-2 mb-2">
        <div class="card bg-primary text-white h-100">
            <div class="card-body text-center py-3">
                <h2 class="mb-0"><?php echo $stats['total']; ?></h2>
                <small>Total</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2 mb-2">
        <div class="card bg-warning h-100">
            <div class="card-body text-center py-3">
                <h2 class="mb-0"><?php echo $stats['waiting']; ?></h2>
                <small>Waiting</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2 mb-2">
        <div class="card bg-info text-white h-100">
            <div class="card-body text-center py-3">
                <h2 class="mb-0"><?php echo $stats['in_progress']; ?></h2>
                <small>In Progress</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2 mb-2">
        <div class="card bg-success text-white h-100">
            <div class="card-body text-center py-3">
                <h2 class="mb-0"><?php echo $stats['completed']; ?></h2>
                <small>Completed</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2 mb-2">
        <div class="card bg-secondary text-white h-100">
            <div class="card-body text-center py-3">
                <h2 class="mb-0"><?php echo $stats['no_show']; ?></h2>
                <small>No Show</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2 mb-2">
        <div class="card bg-danger text-white h-100">
            <div class="card-body text-center py-3">
                <h2 class="mb-0"><?php echo $stats['cancelled']; ?></h2>
                <small>Cancelled</small>
            </div>
        </div>
    </div>
</div>

<!-- Progress Bar -->
<?php if ($stats['total'] > 0): ?>
    <div class="card mb-4">
        <div class="card-body py-2">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <small class="text-muted">Queue Progress</small>
                <small class="text-muted">
                    <?php echo $stats['completed']; ?> / <?php echo $stats['total']; ?> completed
                    (<?php echo $stats['total'] > 0 ? round(($stats['completed'] / $stats['total']) * 100) : 0; ?>%)
                </small>
            </div>
            <div class="progress" style="height: 10px;">
                <div class="progress-bar bg-success" style="width: <?php echo ($stats['completed'] / $stats['total']) * 100; ?>%"></div>
                <div class="progress-bar bg-info" style="width: <?php echo ($stats['in_progress'] / $stats['total']) * 100; ?>%"></div>
                <div class="progress-bar bg-warning" style="width: <?php echo ($stats['waiting'] / $stats['total']) * 100; ?>%"></div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Queue Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-list-ol me-2"></i>Patient Queue
            <span class="badge bg-primary ms-1"><?php echo $stats['total']; ?></span>
        </span>
        <?php if ($stats['total'] > 0): ?>
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-secondary" onclick="filterQueue('all')">All</button>
                <button type="button" class="btn btn-outline-warning" onclick="filterQueue('Waiting')">Waiting</button>
                <button type="button" class="btn btn-outline-info" onclick="filterQueue('In Progress')">In Progress</button>
                <button type="button" class="btn btn-outline-success" onclick="filterQueue('Completed')">Completed</button>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <?php if (empty($queue)): ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox display-1 text-muted"></i>
                <h4 class="text-muted mt-3">No Patients in Queue</h4>
                <p class="text-muted">This session doesn't have any patients yet.</p>
                <a href="add-to-queue.php?session_id=<?php echo $sessionId; ?>" class="btn btn-success btn-lg">
                    <i class="bi bi-person-plus me-2"></i>Add First Patient
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="queueTable">
                    <thead class="table-light">
                        <tr>
                            <th width="70" class="text-center">Queue #</th>
                            <th>Patient Name</th>
                            <th>Patient Code</th>
                            <th>Age / Gender</th>
                            <th>Phone</th>
                            <th>Check-in Time</th>
                            <th width="120">Status</th>
                            <th width="200" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($queue as $index => $patient): ?>
                            <?php
                            $rowClass = match($patient['status']) {
                                'In Progress' => 'table-warning',
                                'Completed' => 'table-success',
                                'Cancelled' => 'table-secondary text-muted',
                                'No Show' => 'table-danger',
                                default => ''
                            };
                            ?>
                            <tr class="<?php echo $rowClass; ?> queue-row" data-status="<?php echo $patient['status']; ?>">
                                <td class="text-center">
                                    <span class="queue-number"><?php echo $patient['queue_number']; ?></span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm me-2">
                                            <?php echo strtoupper(substr($patient['first_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <strong><?php echo htmlspecialchars($patient['patient_name']); ?></strong>
                                            <?php if ($patient['status'] === 'In Progress'): ?>
                                                <span class="badge bg-warning ms-1">
                                                    <i class="bi bi-person-check"></i> With Doctor
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <code><?php echo htmlspecialchars($patient['patient_code']); ?></code>
                                </td>
                                <td>
                                    <?php echo $patient['age']; ?>y / <?php echo $patient['gender']; ?>
                                </td>
                                <td>
                                    <a href="tel:<?php echo htmlspecialchars($patient['phone']); ?>" class="text-decoration-none">
                                        <i class="bi bi-telephone text-success me-1"></i>
                                        <?php echo htmlspecialchars($patient['phone']); ?>
                                    </a>
                                </td>
                                <td>
                                    <i class="bi bi-clock text-muted me-1"></i>
                                    <?php echo formatDateTime($patient['check_in_time'], 'h:i A'); ?>
                                    <?php if ($patient['start_time']): ?>
                                        <br><small class="text-muted">
                                            Started: <?php echo formatDateTime($patient['start_time'], 'h:i A'); ?>
                                        </small>
                                    <?php endif; ?>
                                    <?php if ($patient['end_time']): ?>
                                        <br><small class="text-success">
                                            Ended: <?php echo formatDateTime($patient['end_time'], 'h:i A'); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = match($patient['status']) {
                                        'Waiting' => 'status-waiting',
                                        'In Progress' => 'status-in-progress',
                                        'Completed' => 'status-completed',
                                        'No Show' => 'bg-secondary text-white',
                                        'Cancelled' => 'bg-danger text-white',
                                        default => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="status-badge <?php echo $statusClass; ?>">
                                        <?php 
                                        $statusIcon = match($patient['status']) {
                                            'Waiting' => 'bi-hourglass-split',
                                            'In Progress' => 'bi-person-check',
                                            'Completed' => 'bi-check-circle',
                                            'No Show' => 'bi-person-x',
                                            'Cancelled' => 'bi-x-circle',
                                            default => 'bi-question-circle'
                                        };
                                        ?>
                                        <i class="bi <?php echo $statusIcon; ?> me-1"></i>
                                        <?php echo $patient['status']; ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <!-- View Patient -->
                                        <a href="view-patient.php?id=<?php echo $patient['patient_id']; ?>" 
                                           class="btn btn-outline-info" title="View Patient Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        
                                        <!-- Status Dropdown -->
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary dropdown-toggle" 
                                                    data-bs-toggle="dropdown" aria-expanded="false" title="Change Status">
                                                <i class="bi bi-arrow-repeat"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><h6 class="dropdown-header">Change Status To:</h6></li>
                                                <?php 
                                                $statuses = ['Waiting', 'In Progress', 'Completed', 'No Show', 'Cancelled'];
                                                foreach ($statuses as $status): 
                                                    if ($status !== $patient['status']):
                                                        $icon = match($status) {
                                                            'Waiting' => 'bi-hourglass-split text-warning',
                                                            'In Progress' => 'bi-person-check text-info',
                                                            'Completed' => 'bi-check-circle text-success',
                                                            'No Show' => 'bi-person-x text-secondary',
                                                            'Cancelled' => 'bi-x-circle text-danger',
                                                            default => 'bi-circle'
                                                        };
                                                ?>
                                                    <li>
                                                        <form method="POST" class="d-inline">
                                                            <?php echo csrfField(); ?>
                                                            <input type="hidden" name="action" value="update_patient_status">
                                                            <input type="hidden" name="patient_id" value="<?php echo $patient['patient_id']; ?>">
                                                            <input type="hidden" name="status" value="<?php echo $status; ?>">
                                                            <button type="submit" class="dropdown-item">
                                                                <i class="bi <?php echo $icon; ?> me-2"></i>
                                                                <?php echo $status; ?>
                                                            </button>
                                                        </form>
                                                    </li>
                                                <?php 
                                                    endif;
                                                endforeach; 
                                                ?>
                                            </ul>
                                        </div>
                                        
                                        <!-- Move Up/Down (only for Waiting patients) -->
                                        <?php if ($patient['status'] === 'Waiting'): ?>
                                            <?php if ($index > 0 && $queue[$index - 1]['status'] === 'Waiting'): ?>
                                                <form method="POST" class="d-inline">
                                                    <?php echo csrfField(); ?>
                                                    <input type="hidden" name="action" value="move_up">
                                                    <input type="hidden" name="patient_id" value="<?php echo $patient['patient_id']; ?>">
                                                    <button type="submit" class="btn btn-outline-secondary" title="Move Up">
                                                        <i class="bi bi-arrow-up"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <?php if ($index < count($queue) - 1 && $queue[$index + 1]['status'] === 'Waiting'): ?>
                                                <form method="POST" class="d-inline">
                                                    <?php echo csrfField(); ?>
                                                    <input type="hidden" name="action" value="move_down">
                                                    <input type="hidden" name="patient_id" value="<?php echo $patient['patient_id']; ?>">
                                                    <button type="submit" class="btn btn-outline-secondary" title="Move Down">
                                                        <i class="bi bi-arrow-down"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        
                                        <!-- Remove Button (only for certain statuses) -->
                                        <?php if (in_array($patient['status'], ['Waiting', 'Cancelled', 'No Show'])): ?>
                                            <button type="button" class="btn btn-outline-danger" 
                                                    onclick="confirmRemove(<?php echo $patient['patient_id']; ?>, '<?php echo htmlspecialchars(addslashes($patient['patient_name'])); ?>', <?php echo $patient['queue_number']; ?>)"
                                                    title="Remove from Queue">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if (!empty($queue)): ?>
        <div class="card-footer bg-light">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <small class="text-muted">
                        <i class="bi bi-info-circle me-1"></i>
                        Click status dropdown to update patient status. Use arrows to reorder waiting patients.
                    </small>
                </div>
                <div class="col-md-6 text-md-end">
                    <small class="text-muted">
                        Last updated: <?php echo date('h:i:s A'); ?>
                    </small>
                    <button type="button" class="btn btn-sm btn-outline-primary ms-2" onclick="location.reload();">
                        <i class="bi bi-arrow-clockwise"></i> Refresh
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Remove Patient Confirmation Modal -->
<div class="modal fade" id="removeModal" tabindex="-1" aria-labelledby="removeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="removeModalLabel">
                    <i class="bi bi-person-x me-2"></i>Remove Patient from Queue
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="bi bi-exclamation-triangle text-warning display-4"></i>
                </div>
                <p class="text-center">
                    Are you sure you want to remove <strong id="removePatientName"></strong> 
                    (Queue #<span id="removeQueueNumber"></span>) from this session queue?
                </p>
                <p class="text-center text-muted small">
                    <i class="bi bi-info-circle me-1"></i>
                    This will remove the patient from the queue but will not delete any visit records.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i>Cancel
                </button>
                <form method="POST" id="removeForm" class="d-inline">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="remove_patient">
                    <input type="hidden" name="patient_id" id="removePatientId">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i>Remove from Queue
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
/* Small Avatar */
.avatar-sm {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    background: #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    color: #64748b;
    font-size: 14px;
}

/* Row hover effect */
.queue-row {
    transition: all 0.2s ease;
}

 

/* Print styles */
@media print {
    .btn, .dropdown, .card-footer, .modal {
        display: none !important;
    }
    
    .card {
        border: 1px solid #ddd !important;
        box-shadow: none !important;
    }
    
    .queue-number {
        background: #333 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>

<script>
// Confirm remove patient
function confirmRemove(patientId, patientName, queueNumber) {
    document.getElementById('removePatientId').value = patientId;
    document.getElementById('removePatientName').textContent = patientName;
    document.getElementById('removeQueueNumber').textContent = queueNumber;
    
    const modal = new bootstrap.Modal(document.getElementById('removeModal'));
    modal.show();
}

// Filter queue by status
function filterQueue(status) {
    const rows = document.querySelectorAll('.queue-row');
    
    rows.forEach(row => {
        if (status === 'all') {
            row.style.display = '';
        } else {
            const rowStatus = row.getAttribute('data-status');
            row.style.display = (rowStatus === status) ? '' : 'none';
        }
    });
    
    // Update active button state
    document.querySelectorAll('.card-header .btn-group .btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.textContent.trim().includes(status) || (status === 'all' && btn.textContent.includes('All'))) {
            btn.classList.add('active');
        }
    });
}

// Auto-refresh every 30 seconds (optional - uncomment to enable)
// setInterval(() => {
//     location.reload();
// }, 30000);
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>