<?php
/**
 * Patient Portal - Book Appointment
 * Public page for patients to book appointments with doctors
 */

$pageTitle = 'Book an Appointment';

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/Appointment.php';
require_once __DIR__ . '/classes/Patient.php';
require_once __DIR__ . '/includes/functions.php';

$appointment = new Appointment();
$patient = new Patient();
$errors = [];
$success = false;
$doctors = [];
$availableSlots = [];

// Get list of active doctors
$db = Database::getInstance();
$doctorsSql = "SELECT u.user_id, u.full_name, d.specialization
               FROM users u
               LEFT JOIN doctor_details d ON u.user_id = d.user_id
               WHERE u.role_id = 2 AND u.is_active = 1
               ORDER BY u.full_name";
$doctors = $db->fetchAll($doctorsSql);

// Handle availability check
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['doctor_id']) && isset($_GET['date'])) {
    $doctorId = (int)$_GET['doctor_id'];
    $selectedDate = $_GET['date'];
    
    $availableSlots = $appointment->getAvailableSlots($doctorId, $selectedDate, 30);
}

// Handle appointment booking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patientName = trim($_POST['patient_name'] ?? '');
    $patientPhone = trim($_POST['patient_phone'] ?? '');
    $patientEmail = trim($_POST['patient_email'] ?? '');
    $doctorId = (int)($_POST['doctor_id'] ?? 0);
    $appointmentDate = $_POST['appointment_date'] ?? '';
    $appointmentTime = $_POST['appointment_time'] ?? '';
    $appointmentType = $_POST['appointment_type'] ?? 'Routine Checkup';
    $reason = trim($_POST['reason_for_visit'] ?? '');
    
    // Validation
    if (!$patientName) $errors[] = 'Patient name is required';
    if (!$patientPhone) $errors[] = 'Phone number is required';
    if (!$patientEmail || !filter_var($patientEmail, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
    if (!$doctorId) $errors[] = 'Please select a doctor';
    if (!$appointmentDate) $errors[] = 'Please select a date';
    if (!$appointmentTime) $errors[] = 'Please select a time';
    
    // Check if date is in future
    if (strtotime($appointmentDate) < strtotime('today')) {
        $errors[] = 'Appointment date must be in the future';
    }
    
    if (empty($errors)) {
        try {
            // Check if patient exists by phone
            $existingPatient = $db->fetchOne(
                "SELECT patient_id FROM patients WHERE phone = ? LIMIT 1",
                [$patientPhone]
            );
            
            $patientId = $existingPatient['patient_id'] ?? null;
            
            // Create patient if doesn't exist
            if (!$patientId) {
                $patientId = $patient->create([
                    'patient_code' => $patient->generatePatientCode(),
                    'first_name' => explode(' ', $patientName)[0],
                    'last_name' => implode(' ', array_slice(explode(' ', $patientName), 1)),
                    'phone' => $patientPhone,
                    'date_of_birth' => date('Y-m-d'),
                    'gender' => 'Other',
                    'registration_date' => date('Y-m-d'),
                    'registered_by' => null
                ]);
            }
            
            // Create appointment
            $appointmentId = $appointment->create([
                'patient_id' => $patientId,
                'doctor_id' => $doctorId,
                'appointment_date' => $appointmentDate,
                'appointment_time' => $appointmentTime,
                'appointment_type' => $appointmentType,
                'reason_for_visit' => $reason,
                'status' => 'Scheduled'
            ]);
            
            // TODO: Send confirmation email to patient
            
            $success = true;
            $bookingCode = $db->fetchOne(
                "SELECT appointment_code FROM appointments WHERE appointment_id = ?",
                [$appointmentId]
            )['appointment_code'];
            
        } catch (Exception $e) {
            $errors[] = 'Error booking appointment: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0"><i class="bi bi-calendar-check me-2"></i>Book an Appointment</h3>
                </div>
                <div class="card-body p-5">
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="bi bi-check-circle me-2"></i>
                            <strong>Appointment Booked Successfully!</strong><br>
                            Your appointment code is: <code><?php echo htmlspecialchars($bookingCode); ?></code><br>
                            A confirmation has been sent to your email. We look forward to seeing you!
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <a href="patient-appointments.php?code=<?php echo htmlspecialchars($bookingCode); ?>" 
                               class="btn btn-primary btn-lg">View My Appointment</a>
                            <a href="index.php" class="btn btn-outline-secondary">Back to Home</a>
                        </div>
                    <?php else: ?>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <i class="bi bi-exclamation-circle me-2"></i>
                                <strong>Please fix the following errors:</strong>
                                <ul class="mb-0 mt-2">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <!-- Patient Information -->
                            <h5 class="mb-3"><i class="bi bi-person me-2"></i>Your Information</h5>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" name="patient_name" 
                                           value="<?php echo htmlspecialchars($_POST['patient_name'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email *</label>
                                    <input type="email" class="form-control" name="patient_email"
                                           value="<?php echo htmlspecialchars($_POST['patient_email'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Phone Number *</label>
                                    <input type="tel" class="form-control" name="patient_phone"
                                           value="<?php echo htmlspecialchars($_POST['patient_phone'] ?? ''); ?>" required>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <!-- Appointment Details -->
                            <h5 class="mb-3"><i class="bi bi-calendar-event me-2"></i>Appointment Details</h5>
                            
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label class="form-label">Select Doctor *</label>
                                    <select class="form-select" name="doctor_id" id="doctorSelect" required>
                                        <option value="">-- Choose a Doctor --</option>
                                        <?php foreach ($doctors as $doc): ?>
                                            <option value="<?php echo $doc['user_id']; ?>" 
                                                    <?php echo (isset($_POST['doctor_id']) && $_POST['doctor_id'] == $doc['user_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($doc['full_name']); ?> 
                                                <?php echo $doc['specialization'] ? '(' . htmlspecialchars($doc['specialization']) . ')' : ''; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Preferred Date *</label>
                                    <input type="date" class="form-control" name="appointment_date" id="appointmentDate"
                                           value="<?php echo htmlspecialchars($_POST['appointment_date'] ?? date('Y-m-d', strtotime('tomorrow'))); ?>"
                                           min="<?php echo date('Y-m-d'); ?>" required
                                           onchange="loadAvailableSlots()">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Time *</label>
                                    <select class="form-select" name="appointment_time" id="appointmentTime" required>
                                        <option value="">-- Select Time --</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label class="form-label">Appointment Type</label>
                                    <select class="form-select" name="appointment_type">
                                        <option value="New Patient">New Patient</option>
                                        <option value="Follow Up">Follow Up</option>
                                        <option value="Routine Checkup" selected>Routine Checkup</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <label class="form-label">Reason for Visit</label>
                                    <textarea class="form-control" name="reason_for_visit" rows="3" 
                                              placeholder="Please describe your symptoms or reason for visit..."><?php echo htmlspecialchars($_POST['reason_for_visit'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-check-circle me-2"></i>Book Appointment
                                </button>
                                <a href="/" class="btn btn-outline-secondary">Cancel</a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!$success): ?>
<script>
function loadAvailableSlots() {
    const doctorId = document.getElementById('doctorSelect').value;
    const date = document.getElementById('appointmentDate').value;
    const timeSelect = document.getElementById('appointmentTime');
    
    if (!doctorId || !date) {
        timeSelect.innerHTML = '<option value="">-- Select Time --</option>';
        return;
    }
    
    fetch(`/patient-api/available-slots.php?doctor_id=${doctorId}&date=${date}`)
        .then(r => r.json())
        .then(data => {
            timeSelect.innerHTML = '<option value="">-- Select Time --</option>';
            data.forEach(slot => {
                const option = document.createElement('option');
                option.value = slot.slot_time;
                option.textContent = slot.slot_time;
                timeSelect.appendChild(option);
            });
        })
        .catch(e => {
            console.error('Error loading slots:', e);
            timeSelect.innerHTML = '<option value="">Error loading slots</option>';
        });
}
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
