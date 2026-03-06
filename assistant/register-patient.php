<?php
/**
 * Register New Patient
 */

$pageTitle = 'Register Patient';

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
$formData = [
    'first_name' => '', 'last_name' => '', 'nic_number' => '', 'date_of_birth' => '',
    'gender' => '', 'phone' => '', 'address' => '', 'blood_group' => 'Unknown',
    'allergies' => '', 'chronic_diseases' => '', 'weight' => '', 'height' => '',
    'emergency_contact_name' => '', 'emergency_contact_phone' => '', 'add_to_session' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCSRF();
    
    $formData = [
        'first_name' => sanitize($_POST['first_name'] ?? ''),
        'last_name' => sanitize($_POST['last_name'] ?? ''),
        'nic_number' => sanitize($_POST['nic_number'] ?? ''),
        'date_of_birth' => $_POST['date_of_birth'] ?? '',
        'gender' => $_POST['gender'] ?? '',
        'phone' => sanitize($_POST['phone'] ?? ''),
        'address' => sanitize($_POST['address'] ?? ''),
        'blood_group' => $_POST['blood_group'] ?? 'Unknown',
        'allergies' => sanitize($_POST['allergies'] ?? ''),
        'chronic_diseases' => sanitize($_POST['chronic_diseases'] ?? ''),
        'weight' => $_POST['weight'] ?? '',
        'height' => $_POST['height'] ?? '',
        'emergency_contact_name' => sanitize($_POST['emergency_contact_name'] ?? ''),
        'emergency_contact_phone' => sanitize($_POST['emergency_contact_phone'] ?? ''),
        'add_to_session' => $_POST['add_to_session'] ?? ''
    ];
    
    if (empty($formData['first_name'])) $errors[] = 'First name is required.';
    if (empty($formData['last_name'])) $errors[] = 'Last name is required.';
    if (empty($formData['date_of_birth'])) $errors[] = 'Date of birth is required.';
    if (empty($formData['gender'])) $errors[] = 'Gender is required.';
    if (empty($formData['phone'])) $errors[] = 'Phone number is required.';
    
    if (empty($errors)) {
        try {
            $patientData = array_merge($formData, [
                'patient_code' => $patient->generatePatientCode(),
                'registration_date' => date('Y-m-d'),
                'registered_by' => User::getUserId()
            ]);
            
            $patientId = $patient->create($patientData);
            
            if ($patientId) {
                $message = 'Patient registered! Code: ' . $patientData['patient_code'];
                
                if (!empty($formData['add_to_session'])) {
                    $queueNumber = $clinicSession->addPatientToQueue((int)$formData['add_to_session'], $patientId);
                    $message .= ' | Queue #' . $queueNumber;
                }
                
                redirect(APP_URL . '/assistant/index.php', 'success', $message);
            } else {
                $errors[] = 'Failed to register patient.';
            }
        } catch (Exception $e) {
            $errors[] = 'Error: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h1><i class="bi bi-person-plus me-2"></i>Register New Patient</h1>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST">
    <?php echo csrfField(); ?>
    
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header"><i class="bi bi-person me-2"></i>Personal Information</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="first_name" value="<?php echo htmlspecialchars($formData['first_name']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="last_name" value="<?php echo htmlspecialchars($formData['last_name']); ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">NIC / ID Number</label>
                            <input type="text" class="form-control" name="nic_number" value="<?php echo htmlspecialchars($formData['nic_number']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="date_of_birth" value="<?php echo $formData['date_of_birth']; ?>" max="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Gender <span class="text-danger">*</span></label>
                            <select class="form-select" name="gender" required>
                                <option value="">Select</option>
                                <?php foreach (getGenders() as $g): ?>
                                    <option value="<?php echo $g; ?>" <?php echo $formData['gender'] === $g ? 'selected' : ''; ?>><?php echo $g; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($formData['phone']); ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="address" rows="2"><?php echo htmlspecialchars($formData['address']); ?></textarea>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header"><i class="bi bi-heart-pulse me-2"></i>Medical Information</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Blood Group</label>
                            <select class="form-select" name="blood_group">
                                <?php foreach (getBloodGroups() as $bg): ?>
                                    <option value="<?php echo $bg; ?>" <?php echo $formData['blood_group'] === $bg ? 'selected' : ''; ?>><?php echo $bg; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Weight (kg)</label>
                            <input type="number" step="0.1" class="form-control" name="weight" value="<?php echo $formData['weight']; ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Height (cm)</label>
                            <input type="number" step="0.1" class="form-control" name="height" value="<?php echo $formData['height']; ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Allergies</label>
                        <textarea class="form-control" name="allergies" rows="2"><?php echo htmlspecialchars($formData['allergies']); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Chronic Diseases</label>
                        <textarea class="form-control" name="chronic_diseases" rows="2"><?php echo htmlspecialchars($formData['chronic_diseases']); ?></textarea>
                    </div>
                    <hr>
                    <h6><i class="bi bi-telephone me-2"></i>Emergency Contact</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="emergency_contact_name" value="<?php echo htmlspecialchars($formData['emergency_contact_name']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="emergency_contact_phone" value="<?php echo htmlspecialchars($formData['emergency_contact_phone']); ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (!empty($activeSessions)): ?>
        <div class="card mb-4">
            <div class="card-header"><i class="bi bi-people me-2"></i>Add to Session Queue</div>
            <div class="card-body">
                <div class="col-md-6">
                    <select class="form-select" name="add_to_session">
                        <option value="">-- Don't add to queue --</option>
                        <?php foreach ($activeSessions as $session): ?>
                            <option value="<?php echo $session['session_id']; ?>">
                                <?php echo $session['session_code']; ?> - <?php echo $session['doctor_name']; ?> (<?php echo formatTime($session['start_time']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-check-circle me-2"></i>Register Patient</button>
            <a href="index.php" class="btn btn-secondary btn-lg"><i class="bi bi-x-circle me-2"></i>Cancel</a>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>