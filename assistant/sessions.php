<?php
/**
 * View All Sessions - Assistant Module
 * Manage clinic sessions with delete functionality
 */

$pageTitle = 'Manage Sessions';

require_once __DIR__ . '/../includes/auth.php';
requireAssistant();
require_once __DIR__ . '/../includes/functions.php';   // ADD THIS
require_once __DIR__ . '/../includes/csrf.php';   // ADD THIS
require_once __DIR__ . '/../classes/Session.php';
require_once __DIR__ . '/../classes/User.php';

$clinicSession = new ClinicSession();
$userObj = new User();

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    checkCSRF();
    
    if ($_POST['action'] === 'delete') {
        $sessionId = (int)($_POST['session_id'] ?? 0);
        
        if ($sessionId > 0) {
            $result = $clinicSession->deleteSession($sessionId);
            
            if ($result['success']) {
                redirect(APP_URL . '/assistant/sessions.php', 'success', $result['message']);
            } else {
                redirect(APP_URL . '/assistant/sessions.php', 'danger', $result['message']);
            }
        }
    }
    
    if ($_POST['action'] === 'update_status') {
        $sessionId = (int)($_POST['session_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        
        if ($sessionId > 0 && in_array($status, ['Scheduled', 'Active', 'Completed', 'Cancelled'])) {
            $updated = $clinicSession->updateStatus($sessionId, $status);
            
            if ($updated) {
                redirect(APP_URL . '/assistant/sessions.php', 'success', 'Session status updated successfully!');
            } else {
                redirect(APP_URL . '/assistant/sessions.php', 'danger', 'Failed to update session status.');
            }
        }
    }
}

// Filter parameters
$filterDate = $_GET['date'] ?? '';
$filterDoctor = $_GET['doctor'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$showAll = isset($_GET['all']) && $_GET['all'] === '1';

// Get sessions based on filters
if ($showAll) {
    $sessions = $clinicSession->getAllSessions($filterDate, $filterDoctor, $filterStatus);
} else {
    // Default: show today's sessions
    $sessions = $clinicSession->getTodaySessions();
}

// Get all doctors for filter dropdown
$doctors = $userObj->getAllDoctors();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-calendar-week me-2"></i>Manage Sessions</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Sessions</li>
            </ol>
        </nav>
    </div>
    <a href="create-session.php" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>Create New Session
    </a>
</div>

<?php displayFlash(); ?>

<!-- Filter Card -->
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-funnel me-2"></i>Filter Sessions
    </div>
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <input type="hidden" name="all" value="1">
            
            <div class="col-md-3">
                <label class="form-label">Date</label>
                <input type="date" class="form-control" name="date" value="<?php echo htmlspecialchars($filterDate); ?>">
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Doctor</label>
                <select class="form-select" name="doctor">
                    <option value="">All Doctors</option>
                    <?php foreach ($doctors as $doctor): ?>
                        <option value="<?php echo $doctor['user_id']; ?>" 
                            <?php echo $filterDoctor == $doctor['user_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($doctor['full_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select class="form-select" name="status">
                    <option value="">All Status</option>
                    <option value="Scheduled" <?php echo $filterStatus === 'Scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                    <option value="Active" <?php echo $filterStatus === 'Active' ? 'selected' : ''; ?>>Active</option>
                    <option value="Completed" <?php echo $filterStatus === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="Cancelled" <?php echo $filterStatus === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search me-1"></i>Filter
                </button>
                <a href="sessions.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i>Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Quick View Buttons -->
<div class="mb-4">
    <div class="btn-group" role="group">
        <a href="sessions.php" class="btn <?php echo !$showAll ? 'btn-primary' : 'btn-outline-primary'; ?>">
            <i class="bi bi-calendar-day me-1"></i>Today's Sessions
        </a>
        <a href="sessions.php?all=1" class="btn <?php echo $showAll && !$filterDate ? 'btn-primary' : 'btn-outline-primary'; ?>">
            <i class="bi bi-calendar-range me-1"></i>All Sessions
        </a>
        <a href="sessions.php?all=1&date=<?php echo date('Y-m-d', strtotime('-7 days')); ?>" 
           class="btn btn-outline-primary">
            <i class="bi bi-clock-history me-1"></i>Last 7 Days
        </a>
    </div>
</div>

<!-- Sessions Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-list me-2"></i>
            <?php echo $showAll ? 'All Sessions' : "Today's Sessions"; ?>
            (<?php echo count($sessions); ?>)
        </span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($sessions)): ?>
            <div class="text-center py-5">
                <i class="bi bi-calendar-x display-4 text-muted"></i>
                <p class="text-muted mt-2">No sessions found</p>
                <a href="create-session.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Create Session
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive" style="overflow: visible;">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Session Code</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Doctor</th>
                            <th>Patients</th>
                            <th>Queue Status</th>
                            <th>Status</th>
                            <th width="200">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessions as $session): ?>
                            <tr class="<?php echo $session['status'] === 'Cancelled' ? 'table-secondary' : ''; ?>">
                                <td>
                                    <code class="fs-6"><?php echo htmlspecialchars($session['session_code']); ?></code>
                                </td>
                                <td>
                                    <?php echo formatDate($session['session_date']); ?>
                                    <?php if ($session['session_date'] === date('Y-m-d')): ?>
                                        <span class="badge bg-info ms-1">Today</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo formatTime($session['start_time']); ?></td>
                                <td>
                                    <i class="bi bi-person-badge me-1 text-primary"></i>
                                    <?php echo htmlspecialchars($session['doctor_name']); ?>
                                </td>
                                <td>
                                    <span class="badge bg-primary fs-6"><?php echo $session['total_patients']; ?></span>
                                </td>
                                <td>
                                    <?php if ($session['total_patients'] > 0): ?>
                                        <span class="badge bg-warning me-1" title="Waiting">
                                            <i class="bi bi-hourglass-split"></i> <?php echo $session['waiting_count']; ?>
                                        </span>
                                        <span class="badge bg-success" title="Completed">
                                            <i class="bi bi-check-circle"></i> <?php echo $session['completed_count']; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">No patients</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = match($session['status']) {
                                        'Scheduled' => 'bg-secondary',
                                        'Active' => 'bg-success',
                                        'Completed' => 'bg-primary',
                                        'Cancelled' => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?>">
                                        <?php echo $session['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <!-- View Queue Button -->
                                        <a href="session-queue.php?id=<?php echo $session['session_id']; ?>" 
                                           class="btn btn-outline-primary" title="View Queue">
                                            <i class="bi bi-people"></i>
                                        </a>
                                        
                                        <!-- Status Dropdown -->
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-secondary dropdown-toggle" 
                                                    data-bs-toggle="dropdown" title="Change Status">
                                                <i class="bi bi-toggle-on"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><h6 class="dropdown-header">Change Status</h6></li>
                                                <?php foreach (['Scheduled', 'Active', 'Completed', 'Cancelled'] as $status): ?>
                                                    <?php if ($status !== $session['status']): ?>
                                                        <li>
                                                            <form method="POST" class="d-inline">
                                                                <?php echo csrfField(); ?>
                                                                <input type="hidden" name="action" value="update_status">
                                                                <input type="hidden" name="session_id" value="<?php echo $session['session_id']; ?>">
                                                                <input type="hidden" name="status" value="<?php echo $status; ?>">
                                                                <button type="submit" class="dropdown-item">
                                                                    <?php echo $status; ?>
                                                                </button>
                                                            </form>
                                                        </li>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                        
                                        <!-- Delete Button (only if no patients) -->
                                        <?php if ($session['total_patients'] == 0): ?>
                                            <button type="button" class="btn btn-outline-danger" 
                                                    onclick="confirmDelete(<?php echo $session['session_id']; ?>, '<?php echo htmlspecialchars($session['session_code']); ?>')"
                                                    title="Delete Session">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-outline-secondary" disabled 
                                                    title="Cannot delete - has patients">
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
</div>

<!-- Summary Stats -->
<?php if (!empty($sessions)): ?>
<div class="row mt-4">
    <div class="col-md-3">
        <div class="card bg-light">
            <div class="card-body text-center">
                <h3 class="text-primary mb-0"><?php echo count($sessions); ?></h3>
                <small class="text-muted">Total Sessions</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-light">
            <div class="card-body text-center">
                <h3 class="text-success mb-0">
                    <?php echo count(array_filter($sessions, fn($s) => $s['status'] === 'Active')); ?>
                </h3>
                <small class="text-muted">Active Sessions</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-light">
            <div class="card-body text-center">
                <h3 class="text-info mb-0">
                    <?php echo array_sum(array_column($sessions, 'total_patients')); ?>
                </h3>
                <small class="text-muted">Total Patients</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-light">
            <div class="card-body text-center">
                <h3 class="text-warning mb-0">
                    <?php echo count(array_filter($sessions, fn($s) => $s['total_patients'] == 0)); ?>
                </h3>
                <small class="text-muted">Empty Sessions</small>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle me-2"></i>Confirm Delete
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete session <strong id="deleteSessionCode"></strong>?</p>
                <p class="text-danger mb-0">
                    <i class="bi bi-exclamation-circle me-1"></i>
                    This action cannot be undone.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i>Cancel
                </button>
                <form method="POST" id="deleteForm" class="d-inline">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="session_id" id="deleteSessionId">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i>Delete Session
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(sessionId, sessionCode) {
    document.getElementById('deleteSessionId').value = sessionId;
    document.getElementById('deleteSessionCode').textContent = sessionCode;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>