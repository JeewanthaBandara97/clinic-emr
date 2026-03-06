<?php
/**
 * Admin Dashboard
 * Clinic EMR System
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

$pageTitle = 'Admin Dashboard';

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$user = new User();

// ================================================================
// DASHBOARD STATISTICS — All wrapped in try-catch for safety
// ================================================================

// Total Doctors
$totalDoctors = 0;
try {
    $totalDoctors = $user->getDoctorCount();
} catch (Exception $e) {
    $totalDoctors = 0;
}

// Active Doctors
$activeDoctors = 0;
try {
    $activeDoctors = $user->getActiveDoctorCount();
} catch (Exception $e) {
    $activeDoctors = 0;
}

// Total Assistants
$totalAssistants = 0;
try {
    $totalAssistants = $user->getAssistantCount();
} catch (Exception $e) {
    $totalAssistants = 0;
}

// Active Assistants
$activeAssistants = 0;
try {
    $activeAssistants = $user->getActiveAssistantCount();
} catch (Exception $e) {
    $activeAssistants = 0;
}

// Today's Session Count
$todaySessionCount = 0;
try {
    $todaySessionCount = $user->getTodaySessionCount();
} catch (Exception $e) {
    $todaySessionCount = 0;
}

// Total Patients
$totalPatients = 0;
try {
    $totalPatients = $user->getTotalPatientCount();
} catch (Exception $e) {
    $totalPatients = 0;
}

// Today's New Patients
$todayPatients = 0;
try {
    $todayPatients = $user->getTodayPatientCount();
} catch (Exception $e) {
    $todayPatients = 0;
}

// Today's Visits
$todayVisits = 0;
try {
    $todayVisits = $user->getTodayVisitCount();
} catch (Exception $e) {
    $todayVisits = 0;
}

// Recent Users
$recentUsers = [];
try {
    $recentUsers = $user->getRecentUsers(10);
} catch (Exception $e) {
    $recentUsers = [];
}

// Today's Sessions
$todaySessions = [];
try {
    $todaySessions = $user->getTodaySessionsAdmin();
} catch (Exception $e) {
    $todaySessions = [];
}

// Recent Activity Log
$recentActivity = [];
try {
    $db = Database::getInstance();
    $sql = "SELECT al.*, u.full_name, u.username
            FROM activity_log al
            LEFT JOIN users u ON al.user_id = u.user_id
            ORDER BY al.created_at DESC
            LIMIT 10";
    $recentActivity = $db->fetchAll($sql);
} catch (Exception $e) {
    $recentActivity = [];
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h1><i class="bi bi-shield-lock me-2"></i>Admin Dashboard</h1>
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
            <div class="stat-icon primary">
                <i class="bi bi-heart-pulse"></i>
            </div>
            <div class="stat-value"><?php echo $totalDoctors; ?></div>
            <div class="stat-label">Total Doctors</div>
            <div class="mt-1">
                <small class="text-success">
                    <i class="bi bi-circle-fill me-1" style="font-size:8px;"></i><?php echo $activeDoctors; ?> active
                </small>
                <?php if ($totalDoctors - $activeDoctors > 0): ?>
                    <small class="text-muted ms-2">
                        <i class="bi bi-circle-fill me-1" style="font-size:8px;"></i><?php echo $totalDoctors - $activeDoctors; ?> inactive
                    </small>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="stat-card">
            <div class="stat-icon success">
                <i class="bi bi-person-badge"></i>
            </div>
            <div class="stat-value"><?php echo $totalAssistants; ?></div>
            <div class="stat-label">Total Assistants</div>
            <div class="mt-1">
                <small class="text-success">
                    <i class="bi bi-circle-fill me-1" style="font-size:8px;"></i><?php echo $activeAssistants; ?> active
                </small>
                <?php if ($totalAssistants - $activeAssistants > 0): ?>
                    <small class="text-muted ms-2">
                        <i class="bi bi-circle-fill me-1" style="font-size:8px;"></i><?php echo $totalAssistants - $activeAssistants; ?> inactive
                    </small>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="bi bi-calendar-check"></i>
            </div>
            <div class="stat-value"><?php echo $todaySessionCount; ?></div>
            <div class="stat-label">Today's Sessions</div>
            <?php if ($todayVisits > 0): ?>
                <div class="mt-1">
                    <small class="text-info">
                        <i class="bi bi-clipboard2-pulse me-1"></i><?php echo $todayVisits; ?> visits
                    </small>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="stat-card">
            <div class="stat-icon info">
                <i class="bi bi-people"></i>
            </div>
            <div class="stat-value"><?php echo $totalPatients; ?></div>
            <div class="stat-label">Total Patients</div>
            <?php if ($todayPatients > 0): ?>
                <div class="mt-1">
                    <small class="text-primary">
                        <i class="bi bi-plus-circle me-1"></i><?php echo $todayPatients; ?> new today
                    </small>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-lightning me-2"></i>Quick Actions
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-3 col-6">
                        <a href="<?php echo APP_URL; ?>/admin/doctors.php" class="btn btn-primary w-100">
                            <i class="bi bi-heart-pulse me-2"></i>Manage Doctors
                        </a>
                    </div>
                    <div class="col-md-3 col-6">
                        <a href="<?php echo APP_URL; ?>/admin/assistants.php" class="btn btn-success w-100">
                            <i class="bi bi-person-badge me-2"></i>Manage Assistants
                        </a>
                    </div>
                    <div class="col-md-3 col-6">
                        <a href="<?php echo APP_URL; ?>/admin/doctors.php?action=add" class="btn btn-outline-primary w-100">
                            <i class="bi bi-person-plus me-2"></i>Add Doctor
                        </a>
                    </div>
                    <div class="col-md-3 col-6">
                        <a href="<?php echo APP_URL; ?>/admin/assistants.php?action=add" class="btn btn-outline-success w-100">
                            <i class="bi bi-person-plus me-2"></i>Add Assistant
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Users -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-people-fill me-2"></i>Recent Users</span>
                <div>
                    <a href="<?php echo APP_URL; ?>/admin/doctors.php" class="btn btn-sm btn-outline-primary me-1">Doctors</a>
                    <a href="<?php echo APP_URL; ?>/admin/assistants.php" class="btn btn-sm btn-outline-success">Assistants</a>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentUsers)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-people display-4 text-muted"></i>
                        <p class="text-muted mt-2">No users found</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Full Name</th>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Last Login</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentUsers as $i => $u): ?>
                                    <tr>
                                        <td><?php echo $i + 1; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($u['full_name'] ?? ''); ?></strong>
                                            <?php if (!empty($u['email'])): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($u['email']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><code><?php echo htmlspecialchars($u['username'] ?? ''); ?></code></td>
                                        <td>
                                            <?php
                                            $roleName = $u['role_name'] ?? 'Unknown';
                                            $roleBadges = [
                                                'Admin'     => 'bg-danger',
                                                'Doctor'    => 'bg-primary',
                                                'Assistant' => 'bg-success'
                                            ];
                                            $badge = $roleBadges[$roleName] ?? 'bg-secondary';
                                            ?>
                                            <span class="badge <?php echo $badge; ?>">
                                                <?php echo htmlspecialchars($roleName); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (isset($u['is_active']) && (int)$u['is_active'] === 1): ?>
                                                <span class="status-badge status-completed">Active</span>
                                            <?php else: ?>
                                                <span class="status-badge status-waiting">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($u['last_login'])): ?>
                                                <small><?php echo date('M d, Y h:i A', strtotime($u['last_login'])); ?></small>
                                            <?php else: ?>
                                                <small class="text-muted">Never</small>
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

    <!-- Right Column -->
    <div class="col-lg-4 mb-4">
        <!-- System Summary -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-bar-chart me-2"></i>System Summary
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-heart-pulse me-2 text-primary"></i>Active Doctors</span>
                        <span class="badge bg-primary rounded-pill"><?php echo $activeDoctors; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-person-badge me-2 text-success"></i>Active Assistants</span>
                        <span class="badge bg-success rounded-pill"><?php echo $activeAssistants; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-calendar-check me-2 text-warning"></i>Today's Sessions</span>
                        <span class="badge bg-warning rounded-pill"><?php echo $todaySessionCount; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-clipboard2-pulse me-2 text-info"></i>Today's Visits</span>
                        <span class="badge bg-info rounded-pill"><?php echo $todayVisits; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-people me-2 text-secondary"></i>Total Patients</span>
                        <span class="badge bg-secondary rounded-pill"><?php echo $totalPatients; ?></span>
                    </li>
                    <?php if ($todayPatients > 0): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-person-plus me-2 text-primary"></i>New Patients Today</span>
                        <span class="badge bg-primary rounded-pill"><?php echo $todayPatients; ?></span>
                    </li>
                    <?php endif; ?>
                </ul>
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
                        <i class="bi bi-calendar-x display-6 text-muted"></i>
                        <p class="text-muted mb-0 mt-2">No sessions for today</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($todaySessions as $session): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo htmlspecialchars($session['session_code'] ?? 'N/A'); ?></strong>
                                        <br><small class="text-muted">
                                            <i class="bi bi-heart-pulse me-1"></i>
                                            <?php echo htmlspecialchars($session['doctor_name'] ?? 'N/A'); ?>
                                        </small>
                                        <?php if (!empty($session['start_time'])): ?>
                                            <br><small class="text-muted">
                                                <i class="bi bi-clock me-1"></i>
                                                <?php echo date('h:i A', strtotime($session['start_time'])); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-end">
                                        <?php if (isset($session['total_patients'])): ?>
                                            <span class="badge bg-primary"><?php echo $session['total_patients']; ?> patients</span>
                                        <?php endif; ?>
                                        <?php if (isset($session['waiting_count']) && (int)$session['waiting_count'] > 0): ?>
                                            <br><small class="text-warning">
                                                <i class="bi bi-hourglass-split me-1"></i><?php echo $session['waiting_count']; ?> waiting
                                            </small>
                                        <?php endif; ?>
                                        <?php if (!empty($session['status'])): ?>
                                            <br><span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $session['status'])); ?>">
                                                <?php echo htmlspecialchars($session['status']); ?>
                                            </span>
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

<!-- Recent Activity Log -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-clock-history me-2"></i>Recent Activity</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($recentActivity)): ?>
            <div class="text-center py-4">
                <i class="bi bi-clock display-6 text-muted"></i>
                <p class="text-muted mb-0 mt-2">No recent activity</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentActivity as $activity): ?>
                            <tr>
                                <td>
                                    <small><?php echo date('M d, h:i A', strtotime($activity['created_at'])); ?></small>
                                </td>
                                <td>
                                    <?php if (!empty($activity['full_name'])): ?>
                                        <strong><?php echo htmlspecialchars($activity['full_name']); ?></strong>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($activity['username'] ?? ''); ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">System</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $action = $activity['action'] ?? '';
                                    $actionIcons = [
                                        'Login'  => '<i class="bi bi-box-arrow-in-right text-success me-1"></i>',
                                        'Logout' => '<i class="bi bi-box-arrow-left text-secondary me-1"></i>',
                                        'Create' => '<i class="bi bi-plus-circle text-primary me-1"></i>',
                                        'Update' => '<i class="bi bi-pencil text-warning me-1"></i>',
                                        'Delete' => '<i class="bi bi-trash text-danger me-1"></i>',
                                    ];
                                    $icon = $actionIcons[$action] ?? '<i class="bi bi-activity me-1"></i>';
                                    echo $icon . htmlspecialchars($action);
                                    ?>
                                    <?php if (!empty($activity['table_name'])): ?>
                                        <br><small class="text-muted">on <?php echo htmlspecialchars($activity['table_name']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?php echo htmlspecialchars($activity['details'] ?? '-'); ?></small>
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