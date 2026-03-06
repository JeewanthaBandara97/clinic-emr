<?php
/**
 * Add Patient to Queue
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

$pageTitle = 'Add to Queue';

require_once __DIR__ . '/../includes/auth.php';
requireAssistant();
require_once __DIR__ . '/../includes/functions.php';   // ADD THIS
require_once __DIR__ . '/../includes/csrf.php';   // ADD THIS
require_once __DIR__ . '/../classes/Patient.php';
require_once __DIR__ . '/../classes/Session.php';

$patient = new Patient();
$clinicSession = new ClinicSession();
$activeSessions = $clinicSession->getActiveSessions();

$errors = [];
$selectedPatient = null;
$patientId = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;

// Get patient if ID provided
if ($patientId > 0) {
    $selectedPatient = $patient->getById($patientId);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCSRF();
    
    $patientId = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0;
    $sessionId = isset($_POST['session_id']) ? (int)$_POST['session_id'] : 0;
    
    if ($patientId <= 0) {
        $errors[] = 'Please select a patient.';
    }
    if ($sessionId <= 0) {
        $errors[] = 'Please select a session.';
    }
    
    if (empty($errors)) {
        // Check if patient already in session
        if ($clinicSession->isPatientInSession($sessionId, $patientId)) {
            $errors[] = 'This patient is already in the selected session queue.';
        } else {
            try {
                $queueNumber = $clinicSession->addPatientToQueue($sessionId, $patientId);
                
                if ($queueNumber) {
                    redirect(APP_URL . '/assistant/index.php', 'success', 'Patient added to queue successfully! Queue Number: ' . $queueNumber);
                } else {
                    $errors[] = 'Failed to add patient to queue.';
                }
            } catch (Exception $e) {
                $errors[] = 'Error: ' . $e->getMessage();
            }
        }
    }
    
    // Reload selected patient after POST
    if ($patientId > 0) {
        $selectedPatient = $patient->getById($patientId);
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h1><i class="bi bi-people me-2"></i>Add Patient to Queue</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Add to Queue</li>
        </ol>
    </nav>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-circle me-2"></i>
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (empty($activeSessions)): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-2"></i>
        No active sessions available for today. 
        <a href="create-session.php" class="alert-link">Create a session</a> first.
    </div>
<?php else: ?>
    <div class="row">
        <!-- Patient Search -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-search me-2"></i>Search Patient
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <input type="text" class="form-control form-control-lg" id="patientSearch" 
                               placeholder="Search by name, patient code, NIC, or phone...">
                    </div>
                    <div id="searchResults">
                        <?php if ($selectedPatient): ?>
                            <div class="alert alert-success mb-0">
                                <i class="bi bi-check-circle me-2"></i>
                                <strong>Selected:</strong> 
                                <?php echo htmlspecialchars($selectedPatient['full_name']); ?> 
                                (<?php echo htmlspecialchars($selectedPatient['patient_code']); ?>)
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-search display-4"></i>
                                <p class="mt-2">Enter search term to find patients</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-body text-center">
                    <p class="mb-2">Patient not found?</p>
                    <a href="register-patient.php" class="btn btn-success">
                        <i class="bi bi-person-plus me-2"></i>Register New Patient
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Add to Queue Form -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-plus-circle me-2"></i>Add to Session Queue
                </div>
                <div class="card-body">
                    <form method="POST" action="" id="queueForm">
                        <?php echo csrfField(); ?>
                        
                        <input type="hidden" name="patient_id" id="selectedPatientId" 
                               value="<?php echo $selectedPatient ? $selectedPatient['patient_id'] : ''; ?>">
                        
                        <!-- Selected Patient Display -->
                        <div class="mb-4">
                            <label class="form-label">Selected Patient</label>
                            <div id="selectedPatientDisplay" class="p-3 border rounded bg-light">
                                <?php if ($selectedPatient): ?>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                             style="width:45px;height:45px;font-weight:bold;">
                                            <?php echo strtoupper(substr($selectedPatient['first_name'], 0, 1)); ?>
                                        </div>
                                        <div class="ms-3">
                                            <h6 class="mb-0"><?php echo htmlspecialchars($selectedPatient['full_name']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($selectedPatient['patient_code']); ?> | 
                                                <?php echo $selectedPatient['age']; ?>y | 
                                                <?php echo $selectedPatient['gender']; ?>
                                            </small>
                                        </div>
                                        <div class="ms-auto">
                                            <i class="bi bi-check-circle-fill text-success fs-4"></i>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center text-muted">
                                        <i class="bi bi-person-x"></i> No patient selected
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Session Selection -->
                        <div class="mb-4">
                            <label for="session_id" class="form-label">Select Session <span class="text-danger">*</span></label>
                            <select class="form-select form-select-lg" id="session_id" name="session_id" required>
                                <option value="">-- Select a session --</option>
                                <?php foreach ($activeSessions as $session): ?>
                                    <option value="<?php echo $session['session_id']; ?>">
                                        <?php echo htmlspecialchars($session['session_code']); ?> - 
                                        Dr. <?php echo htmlspecialchars($session['doctor_name']); ?> 
                                        (<?php echo formatTime($session['start_time']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg w-100" id="submitBtn" 
                                <?php echo !$selectedPatient ? 'disabled' : ''; ?>>
                            <i class="bi bi-plus-circle me-2"></i>Add to Queue
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Available Sessions -->
            <div class="card mt-3">
                <div class="card-header">
                    <i class="bi bi-calendar3 me-2"></i>Today's Sessions
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($activeSessions as $session): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0"><?php echo htmlspecialchars($session['doctor_name']); ?></h6>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($session['session_code']); ?> | 
                                        <?php echo formatTime($session['start_time']); ?>
                                    </small>
                                </div>
                                <span class="badge bg-<?php echo $session['status'] === 'Active' ? 'success' : 'secondary'; ?>">
                                    <?php echo $session['status']; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<style>
.patient-result-item {
    cursor: pointer;
    transition: all 0.2s ease;
}
.patient-result-item:hover {
    background-color: #f8f9fa;
}
.patient-result-item.selected {
    background-color: #e7f1ff;
    border-color: #2563eb;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('patientSearch');
    const searchResults = document.getElementById('searchResults');
    const selectedPatientId = document.getElementById('selectedPatientId');
    const selectedPatientDisplay = document.getElementById('selectedPatientDisplay');
    const submitBtn = document.getElementById('submitBtn');
    
    let searchTimeout;
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const term = this.value.trim();
            
            if (term.length < 2) {
                searchResults.innerHTML = '<div class="text-center text-muted py-4"><i class="bi bi-search display-4"></i><p class="mt-2">Enter at least 2 characters</p></div>';
                return;
            }
            
            searchResults.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';
            
            searchTimeout = setTimeout(function() {
                fetch('ajax/search-patient.php?term=' + encodeURIComponent(term))
                    .then(response => response.json())
                    .then(data => {
                        if (data.length === 0) {
                            searchResults.innerHTML = '<div class="text-center text-muted py-4"><i class="bi bi-person-x display-4"></i><p class="mt-2">No patients found</p></div>';
                            return;
                        }
                        
                        let html = '<div class="list-group">';
                        data.forEach(function(patient) {
                            html += '<div class="list-group-item patient-result-item" data-id="' + patient.patient_id + '" data-name="' + patient.full_name + '" data-code="' + patient.patient_code + '" data-age="' + patient.age + '" data-gender="' + patient.gender + '">';
                            html += '<div class="d-flex align-items-center">';
                            html += '<div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width:40px;height:40px;">' + patient.first_name.charAt(0).toUpperCase() + '</div>';
                            html += '<div class="flex-grow-1">';
                            html += '<h6 class="mb-0">' + patient.full_name + '</h6>';
                            html += '<small class="text-muted">' + patient.patient_code + ' | ' + patient.age + 'y | ' + patient.gender + ' | ' + patient.phone + '</small>';
                            html += '</div>';
                            html += '<i class="bi bi-chevron-right text-muted"></i>';
                            html += '</div></div>';
                        });
                        html += '</div>';
                        searchResults.innerHTML = html;
                        
                        // Add click handlers
                        document.querySelectorAll('.patient-result-item').forEach(function(item) {
                            item.addEventListener('click', function() {
                                selectPatient(this);
                            });
                        });
                    })
                    .catch(error => {
                        searchResults.innerHTML = '<div class="alert alert-danger">Error searching. Please try again.</div>';
                    });
            }, 300);
        });
    }
    
    function selectPatient(element) {
        document.querySelectorAll('.patient-result-item').forEach(function(item) {
            item.classList.remove('selected');
        });
        element.classList.add('selected');
        
        const id = element.dataset.id;
        const name = element.dataset.name;
        const code = element.dataset.code;
        const age = element.dataset.age;
        const gender = element.dataset.gender;
        
        selectedPatientId.value = id;
        
        selectedPatientDisplay.innerHTML = '<div class="d-flex align-items-center">' +
            '<div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:45px;height:45px;font-weight:bold;">' + name.charAt(0).toUpperCase() + '</div>' +
            '<div class="ms-3"><h6 class="mb-0">' + name + '</h6><small class="text-muted">' + code + ' | ' + age + 'y | ' + gender + '</small></div>' +
            '<div class="ms-auto"><i class="bi bi-check-circle-fill text-success fs-4"></i></div></div>';
        
        submitBtn.disabled = false;
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>