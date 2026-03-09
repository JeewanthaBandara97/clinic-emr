<?php
/**
 * Doctor - Appointments
 * View and manage appointments
 */

$pageTitle = 'My Appointments';

require_once __DIR__ . '/../includes/auth.php';
requireDoctor();
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../classes/Appointment.php';

$appointment = new Appointment();
$doctorId = User::getUserId();
$view = $_GET['view'] ?? 'calendar'; // calendar, list
$fromDate = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-d');
$toDate = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d', strtotime('+30 days'));
$appointmentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$errors = [];

// Handle appointment status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    checkCSRF();
    
    if ($_POST['action'] === 'update_status') {
        $appointmentId = (int)$_POST['appointment_id'];
        $newStatus = $_POST['status'];
        
        try {
            if ($appointment->updateStatus($appointmentId, $newStatus)) {
                setFlash('success', 'Appointment status updated!');
                redirect(APP_URL . '/doctor/appointments.php?id=' . $appointmentId);
            }
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h1><i class="bi bi-calendar-event me-2"></i>My Appointments</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Appointments</li>
        </ol>
    </nav>
</div>

<?php displayFlash(); ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $error): ?>
            <p><?php echo htmlspecialchars($error); ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- View Options -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-6">
                <form method="GET" class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label">From</label>
                        <input type="date" class="form-control" name="from_date" value="<?php echo $fromDate; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">To</label>
                        <input type="date" class="form-control" name="to_date" value="<?php echo $toDate; ?>">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search me-1"></i>Filter
                        </button>
                    </div>
                </form>
            </div>
            <div class="col-md-6 text-end">
                <a href="?view=list&from_date=<?php echo $fromDate; ?>&to_date=<?php echo $toDate; ?>" 
                   class="btn btn-outline-secondary <?php echo $view === 'list' ? 'active' : ''; ?>">
                    <i class="bi bi-list-ul me-1"></i>List
                </a>
                <a href="?view=calendar&from_date=<?php echo $fromDate; ?>&to_date=<?php echo $toDate; ?>" 
                   class="btn btn-outline-secondary <?php echo $view === 'calendar' ? 'active' : ''; ?>">
                    <i class="bi bi-calendar me-1"></i>Calendar
                </a>
            </div>
        </div>
    </div>
</div>

<?php
// Get appointments
$appointments = $appointment->getDoctorAppointments($doctorId, $fromDate, $toDate);
?>

<?php if ($appointmentId && isset($_GET['view_detail'])): ?>
    <!-- Appointment Detail -->
    <?php
    $apt = $appointment->getById($appointmentId);
    if (!$apt || $apt['doctor_id'] != $doctorId):
        redirect(APP_URL . '/doctor/appointments.php', 'danger', 'Appointment not found.');
    endif;
    ?>
    
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0"><?php echo htmlspecialchars($apt['patient_name']); ?></h5>
                <small class="text-muted">Appointment: <?php echo htmlspecialchars($apt['appointment_code']); ?></small>
            </div>
            <a href="appointments.php?from_date=<?php echo $fromDate; ?>&to_date=<?php echo $toDate; ?>" 
               class="btn btn-outline-secondary">Back</a>
        </div>
        
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6><strong>Appointment Details:</strong></h6>
                    <p>
                        <i class="bi bi-calendar3"></i> <?php echo formatDate($apt['appointment_date']); ?> at 
                        <i class="bi bi-clock"></i> <?php echo substr($apt['appointment_time'], 0, 5); ?>
                    </p>
                    <p><strong>Type:</strong> <?php echo $apt['appointment_type']; ?></p>
                    <p><strong>Reason:</strong> <?php echo htmlspecialchars($apt['reason_for_visit']); ?></p>
                </div>
                <div class="col-md-6">
                    <h6><strong>Patient Information:</strong></h6>
                    <p>
                        <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($apt['patient_phone']); ?>
                    </p>
                    <p>
                        <strong>Status:</strong>
                        <span class="badge bg-<?php
                            echo match($apt['status']) {
                                'Completed' => 'success',
                                'In Progress' => 'warning',
                                'Confirmed', 'Scheduled' => 'info',
                                'Cancelled' => 'danger',
                                default => 'secondary'
                            };
                        ?>">
                            <?php echo ucfirst($apt['status']); ?>
                        </span>
                    </p>
                </div>
            </div>
            
            <!-- Update Status Form -->
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Update Status</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="appointment_id" value="<?php echo $appointmentId; ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">New Status</label>
                                <select class="form-select" name="status" required>
                                    <option value="Scheduled">Scheduled</option>
                                    <option value="Confirmed">Confirmed</option>
                                    <option value="In Progress">In Progress</option>
                                    <option value="Completed">Completed</option>
                                    <option value="Cancelled">Cancelled</option>
                                    <option value="No Show">No Show</option>
                                </select>
                            </div>
                            <div class="col-md-6 d-flex align-items-end mb-3">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-check-circle me-2"></i>Update
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- Appointments List -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bi bi-calendar-event me-2"></i>
                Appointments from <?php echo formatDate($fromDate); ?> to <?php echo formatDate($toDate); ?>
                <span class="badge bg-primary float-end"><?php echo count($appointments); ?> appointments</span>
            </h5>
        </div>
        
        <?php if (empty($appointments)): ?>
            <div class="card-body text-center py-5">
                <i class="bi bi-calendar-x display-4 text-muted"></i>
                <p class="text-muted mt-3">No appointments scheduled for this period</p>
            </div>
        <?php else: ?>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date & Time</th>
                                <th>Patient</th>
                                <th>Patient Code</th>
                                <th>Contact</th>
                                <th>Type</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $apt): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo formatDate($apt['appointment_date']); ?></strong><br>
                                        <small class="text-muted"><?php echo substr($apt['appointment_time'], 0, 5); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($apt['patient_name']); ?></td>
                                    <td><code><?php echo htmlspecialchars($apt['patient_code']); ?></code></td>
                                    <td><?php echo htmlspecialchars($apt['patient_phone']); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo $apt['appointment_type']; ?></span></td>
                                    <td><?php echo htmlspecialchars(substr($apt['reason_for_visit'] ?? '', 0, 30)); ?></td>
                                    <td>
                                        <span class="badge bg-<?php
                                            echo match($apt['status']) {
                                                'Completed' => 'success',
                                                'In Progress' => 'warning',
                                                'Confirmed', 'Scheduled' => 'info',
                                                'Cancelled' => 'danger',
                                                default => 'secondary'
                                            };
                                        ?>">
                                            <?php echo $apt['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="appointments.php?id=<?php echo $apt['appointment_id']; ?>&view_detail=1&from_date=<?php echo $fromDate; ?>&to_date=<?php echo $toDate; ?>" 
                                           class="btn btn-sm btn-primary">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                        <a href="create-visit.php?patient_id=<?php echo $apt['patient_id']; ?>" 
                                           class="btn btn-sm btn-success">
                                            <i class="bi bi-pencil"></i> Visit
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Today's Appointments Summary -->
    <?php
    $todayAppointments = $appointment->getTodayAppointments($doctorId);
    if (!empty($todayAppointments)):
    ?>
        <div class="card mt-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-star me-2"></i>Today's Appointments</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Time</th>
                                <th>Patient</th>
                                <th>Status</th>
                                <th>Contact</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($todayAppointments as $apt): ?>
                                <tr>
                                    <td><strong><?php echo substr($apt['appointment_time'], 0, 5); ?></strong></td>
                                    <td><?php echo htmlspecialchars($apt['patient_name']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $apt['status'] === 'In Progress' ? 'warning' : 'info'; ?>">
                                            <?php echo $apt['status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($apt['patient_phone']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
