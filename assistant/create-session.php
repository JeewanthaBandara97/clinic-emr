<?php
/**
 * Create Clinic Session
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

$pageTitle = 'Create Session';

require_once __DIR__ . '/../includes/auth.php';
requireAssistant();

require_once __DIR__ . '/../classes/Session.php';

$clinicSession = new ClinicSession();
$userObj = new User();
$doctors = $userObj->getAllDoctors();

$errors = [];
$formData = [
    'doctor_id' => '',
    'session_date' => date('Y-m-d'),
    'start_time' => '09:00',
    'max_patients' => 50,
    'notes' => ''
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //checkCSRF();
    
    $formData['doctor_id'] = $_POST['doctor_id'] ?? '';
    $formData['session_date'] = $_POST['session_date'] ?? '';
    $formData['start_time'] = $_POST['start_time'] ?? '';
    $formData['max_patients'] = $_POST['max_patients'] ?? 50;
    $formData['notes'] = trim($_POST['notes'] ?? '');
    
    // Validation
    if (empty($formData['doctor_id'])) {
        $errors[] = 'Please select a doctor.';
    }
    if (empty($formData['session_date'])) {
        $errors[] = 'Session date is required.';
    }
    if (empty($formData['start_time'])) {
        $errors[] = 'Start time is required.';
    }
    
    // Create session if no errors
    if (empty($errors)) {
        try {
            $sessionData = [
                'session_code' => $clinicSession->generateSessionCode(),
                'doctor_id' => (int) $formData['doctor_id'],
                'session_date' => $formData['session_date'],
                'start_time' => $formData['start_time'],
                'max_patients' => (int) $formData['max_patients'],
                'notes' => $formData['notes'],
                'created_by' => User::getUserId()
            ];
            
            $sessionId = $clinicSession->create($sessionData);
            
            if ($sessionId) {
                redirect(APP_URL . '/assistant/index.php', 'success', 'Session created successfully! Code: ' . $sessionData['session_code']);
            } else {
                $errors[] = 'Failed to create session. Please try again.';
            }
        } catch (Exception $e) {
            $errors[] = 'An error occurred: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h1><i class="bi bi-calendar-plus me-2"></i>Create Clinic Session</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Create Session</li>
        </ol>
    </nav>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-plus-circle me-2"></i>Session Details
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-circle me-2"></i>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <?php echo csrfField(); ?>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="doctor_id" class="form-label">Doctor <span class="text-danger">*</span></label>
                            <select class="form-select" id="doctor_id" name="doctor_id" required>
                                <option value="">Select Doctor</option>
                                <?php foreach ($doctors as $doctor): ?>
                                    <option value="<?php echo $doctor['user_id']; ?>"
                                        <?php echo $formData['doctor_id'] == $doctor['user_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($doctor['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="session_date" class="form-label">Session Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="session_date" name="session_date" 
                                   value="<?php echo htmlspecialchars($formData['session_date']); ?>" 
                                   min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="start_time" class="form-label">Start Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" id="start_time" name="start_time" 
                                   value="<?php echo htmlspecialchars($formData['start_time']); ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="max_patients" class="form-label">Max Patients</label>
                            <input type="number" class="form-control" id="max_patients" name="max_patients" 
                                   value="<?php echo htmlspecialchars($formData['max_patients']); ?>" min="1" max="100">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" 
                                  placeholder="Optional notes for this session"><?php echo htmlspecialchars($formData['notes']); ?></textarea>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Create Session
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-2"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card bg-light">
            <div class="card-header">
                <i class="bi bi-info-circle me-2"></i>Information
            </div>
            <div class="card-body">
                <p class="mb-2"><strong>Session Code:</strong> Will be auto-generated</p>
                <p class="mb-2"><strong>Status:</strong> Session will be created as "Scheduled"</p>
                <hr>
                <small class="text-muted">
                    <i class="bi bi-lightbulb me-1"></i>
                    After creating a session, you can add patients to the queue.
                </small>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>