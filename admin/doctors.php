<?php
/**
 * Doctor Management
 * Clinic EMR System
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

$pageTitle = 'Manage Doctors';

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$user = new User();

// Fetch all doctors with details
try {
    $doctors = $user->getAllDoctorsWithDetails();
} catch (Exception $e) {
    $doctors = [];
}

$activeDoctors = count(array_filter($doctors, fn($d) => (int)$d['is_active'] === 1));
$inactiveDoctors = count($doctors) - $activeDoctors;

// Auto-open add modal
$autoOpenAdd = isset($_GET['action']) && $_GET['action'] === 'add';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-heart-pulse me-2"></i>Doctor Management</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo APP_URL; ?>/admin/index.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Doctors</li>
                </ol>
            </nav>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#doctorModal" onclick="resetDoctorForm()">
            <i class="bi bi-person-plus me-2"></i>Add Doctor
        </button>
    </div>
</div>

<?php displayFlash(); ?>

<div id="doctorAlert"></div>

<!-- Statistics -->
<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="bi bi-heart-pulse"></i>
            </div>
            <div class="stat-value"><?php echo count($doctors); ?></div>
            <div class="stat-label">Total Doctors</div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="stat-card">
            <div class="stat-icon success">
                <i class="bi bi-check-circle"></i>
            </div>
            <div class="stat-value"><?php echo $activeDoctors; ?></div>
            <div class="stat-label">Active Doctors</div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="bi bi-pause-circle"></i>
            </div>
            <div class="stat-value"><?php echo $inactiveDoctors; ?></div>
            <div class="stat-label">Inactive Doctors</div>
        </div>
    </div>
</div>

<!-- Doctors Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-table me-2"></i>All Doctors</span>
        <span class="badge bg-primary"><?php echo count($doctors); ?> total</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($doctors)): ?>
            <div class="text-center py-5">
                <i class="bi bi-heart-pulse display-4 text-muted"></i>
                <p class="text-muted mt-2">No doctors found</p>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#doctorModal" onclick="resetDoctorForm()">
                    <i class="bi bi-person-plus me-2"></i>Add First Doctor
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Doctor</th>
                            <th>Specialization</th>
                            <th>Qualification</th>
                            <th>License No.</th>
                            <th>Fee</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($doctors as $i => $doc): ?>
                            <tr id="row-<?php echo $doc['user_id']; ?>">
                                <td><?php echo $i + 1; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($doc['full_name']); ?></strong>
                                    <br><small class="text-muted">
                                        <i class="bi bi-person me-1"></i><?php echo htmlspecialchars($doc['username']); ?>
                                    </small>
                                    <?php if (!empty($doc['email'])): ?>
                                        <br><small class="text-muted">
                                            <i class="bi bi-envelope me-1"></i><?php echo htmlspecialchars($doc['email']); ?>
                                        </small>
                                    <?php endif; ?>
                                    <?php if (!empty($doc['phone'])): ?>
                                        <br><small class="text-muted">
                                            <i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($doc['phone']); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($doc['specialization'])): ?>
                                        <span class="badge bg-info text-dark"><?php echo htmlspecialchars($doc['specialization']); ?></span>
                                    <?php else: ?>
                                        <small class="text-muted">Not set</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($doc['qualification'])): ?>
                                        <?php echo htmlspecialchars($doc['qualification']); ?>
                                    <?php else: ?>
                                        <small class="text-muted">—</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($doc['license_number'])): ?>
                                        <code><?php echo htmlspecialchars($doc['license_number']); ?></code>
                                    <?php else: ?>
                                        <small class="text-muted">—</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($doc['consultation_fee']) && $doc['consultation_fee'] > 0): ?>
                                        <strong>Rs. <?php echo number_format($doc['consultation_fee'], 2); ?></strong>
                                    <?php else: ?>
                                        <small class="text-muted">—</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ((int)$doc['is_active'] === 1): ?>
                                        <span class="status-badge status-completed">Active</span>
                                    <?php else: ?>
                                        <span class="status-badge status-waiting">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($doc['last_login'])): ?>
                                        <small><?php echo date('M d, Y', strtotime($doc['last_login'])); ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">Never</small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-info" onclick="viewDoctor(<?php echo $doc['user_id']; ?>)" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-outline-primary" onclick="editDoctor(<?php echo $doc['user_id']; ?>)" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <?php if ((int)$doc['is_active'] === 1): ?>
                                            <button class="btn btn-outline-warning" onclick="toggleStatus(<?php echo $doc['user_id']; ?>, 0)" title="Deactivate">
                                                <i class="bi bi-pause-circle"></i>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-outline-success" onclick="toggleStatus(<?php echo $doc['user_id']; ?>, 1)" title="Activate">
                                                <i class="bi bi-check-circle"></i>
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

<!-- ==================== ADD/EDIT DOCTOR MODAL ==================== -->
<div class="modal fade" id="doctorModal" tabindex="-1" aria-labelledby="doctorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="doctorModalLabel">
                    <i class="bi bi-person-plus me-2"></i>Add Doctor
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="doctorForm">
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="doctor_user_id" value="">
                    <input type="hidden" name="role" value="doctor">

                    <!-- Nav tabs -->
                    <ul class="nav nav-tabs mb-3" id="doctorTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" 
                                    data-bs-target="#basicInfo" type="button" role="tab">
                                <i class="bi bi-person me-1"></i>Basic Info
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="professional-tab" data-bs-toggle="tab" 
                                    data-bs-target="#professionalInfo" type="button" role="tab">
                                <i class="bi bi-award me-1"></i>Professional Details
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="schedule-tab" data-bs-toggle="tab" 
                                    data-bs-target="#scheduleInfo" type="button" role="tab">
                                <i class="bi bi-calendar-week me-1"></i>Schedule & Fee
                            </button>
                        </li>
                    </ul>

                    <!-- Tab content -->
                    <div class="tab-content" id="doctorTabContent">

                        <!-- Tab 1: Basic Info -->
                        <div class="tab-pane fade show active" id="basicInfo" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="doctor_full_name" class="form-label">
                                        Full Name <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="doctor_full_name" 
                                           name="full_name" required placeholder="Dr. John Smith">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="doctor_username" class="form-label">
                                        Username <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="doctor_username" 
                                           name="username" required placeholder="drjohn" autocomplete="off">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="doctor_email" class="form-label">
                                        Email <span class="text-danger">*</span>
                                    </label>
                                    <input type="email" class="form-control" id="doctor_email" 
                                           name="email" required placeholder="doctor@clinic.com">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="doctor_phone" class="form-label">Phone</label>
                                    <input type="text" class="form-control" id="doctor_phone" 
                                           name="phone" placeholder="0771234567">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="doctor_password" class="form-label">
                                        Password <span class="text-danger" id="doctor_pwd_required">*</span>
                                    </label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="doctor_password" 
                                               name="password" autocomplete="new-password" placeholder="Minimum 6 characters">
                                        <button class="btn btn-outline-secondary" type="button" 
                                                onclick="togglePassword('doctor_password', this)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted" id="doctor_pwd_hint" style="display:none;">
                                        Leave blank to keep current password.
                                    </small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="doctor_status" class="form-label">Status</label>
                                    <select class="form-select" id="doctor_status" name="is_active">
                                        <option value="1">Active</option>
                                        <option value="0">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 2: Professional Details -->
                        <div class="tab-pane fade" id="professionalInfo" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="doctor_specialization" class="form-label">
                                        Specialization <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="doctor_specialization" name="specialization" required>
                                        <option value="">Select Specialization</option>
                                        <option value="General Medicine">General Medicine</option>
                                        <option value="Cardiology">Cardiology</option>
                                        <option value="Dermatology">Dermatology</option>
                                        <option value="ENT">ENT (Ear, Nose, Throat)</option>
                                        <option value="Gastroenterology">Gastroenterology</option>
                                        <option value="Gynecology">Gynecology</option>
                                        <option value="Neurology">Neurology</option>
                                        <option value="Ophthalmology">Ophthalmology</option>
                                        <option value="Orthopedics">Orthopedics</option>
                                        <option value="Pediatrics">Pediatrics</option>
                                        <option value="Psychiatry">Psychiatry</option>
                                        <option value="Pulmonology">Pulmonology</option>
                                        <option value="Radiology">Radiology</option>
                                        <option value="Surgery">Surgery</option>
                                        <option value="Urology">Urology</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="doctor_qualification" class="form-label">
                                        Qualification <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="doctor_qualification" 
                                           name="qualification" required placeholder="MBBS, MD, FRCS etc.">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="doctor_license" class="form-label">License Number</label>
                                    <input type="text" class="form-control" id="doctor_license" 
                                           name="license_number" placeholder="SLMC-12345">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="doctor_experience" class="form-label">Experience (Years)</label>
                                    <input type="number" class="form-control" id="doctor_experience" 
                                           name="experience_years" min="0" max="60" value="0" placeholder="0">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label for="doctor_bio" class="form-label">Bio / About</label>
                                    <textarea class="form-control" id="doctor_bio" name="bio" rows="3" 
                                              placeholder="Brief description about the doctor..."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 3: Schedule & Fee -->
                        <div class="tab-pane fade" id="scheduleInfo" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="doctor_fee" class="form-label">Consultation Fee (Rs.)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rs.</span>
                                        <input type="number" class="form-control" id="doctor_fee" 
                                               name="consultation_fee" min="0" step="0.01" value="0.00" placeholder="1500.00">
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Available Days</label>
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php 
                                        $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                                        foreach ($days as $day): 
                                        ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="available_days[]" value="<?php echo $day; ?>" 
                                                       id="day_<?php echo $day; ?>"
                                                       <?php echo in_array($day, ['Mon','Tue','Wed','Thu','Fri']) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="day_<?php echo $day; ?>"><?php echo $day; ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="doctor_time_start" class="form-label">Available From</label>
                                    <input type="time" class="form-control" id="doctor_time_start" 
                                           name="available_time_start" value="08:00">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="doctor_time_end" class="form-label">Available Until</label>
                                    <input type="time" class="form-control" id="doctor_time_end" 
                                           name="available_time_end" value="17:00">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" id="saveDoctorBtn">
                        <i class="bi bi-check-circle me-1"></i>Save Doctor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==================== VIEW DOCTOR MODAL ==================== -->
<div class="modal fade" id="viewDoctorModal" tabindex="-1" aria-labelledby="viewDoctorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="viewDoctorModalLabel">
                    <i class="bi bi-person-lines-fill me-2"></i>Doctor Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewDoctorBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">Loading...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="viewEditBtn" onclick="">
                    <i class="bi bi-pencil me-1"></i>Edit Doctor
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// ===== Reset Form =====
function resetDoctorForm() {
    document.getElementById('doctorForm').reset();
    document.getElementById('doctor_user_id').value = '';
    document.getElementById('doctorModalLabel').innerHTML = '<i class="bi bi-person-plus me-2"></i>Add Doctor';
    document.getElementById('doctor_pwd_required').style.display = 'inline';
    document.getElementById('doctor_pwd_hint').style.display = 'none';
    document.getElementById('doctor_password').setAttribute('required', 'required');
    
    // Reset to first tab
    var firstTab = new bootstrap.Tab(document.getElementById('basic-tab'));
    firstTab.show();
    
    // Reset day checkboxes to weekdays
    ['Mon','Tue','Wed','Thu','Fri'].forEach(function(d) {
        let cb = document.getElementById('day_' + d);
        if (cb) cb.checked = true;
    });
    ['Sat','Sun'].forEach(function(d) {
        let cb = document.getElementById('day_' + d);
        if (cb) cb.checked = false;
    });
}

// ===== View Doctor Details =====
function viewDoctor(id) {
    document.getElementById('viewDoctorBody').innerHTML = 
        '<div class="text-center py-4"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted">Loading...</p></div>';
    
    var modal = new bootstrap.Modal(document.getElementById('viewDoctorModal'));
    modal.show();
    
    $.ajax({
        url: '<?php echo APP_URL; ?>/admin/ajax/get_user.php',
        type: 'GET',
        data: { user_id: id, role: 'doctor', details: 1 },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                let d = res.data;
                let days = d.available_days ? d.available_days.split(',').map(function(day) {
                    return '<span class="badge bg-primary me-1">' + day.trim() + '</span>';
                }).join('') : '<span class="text-muted">Not set</span>';
                
                let timeStart = d.available_time_start ? formatTime12(d.available_time_start) : '—';
                let timeEnd = d.available_time_end ? formatTime12(d.available_time_end) : '—';
                
                let html = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="bi bi-person me-2"></i>Basic Information
                            </h6>
                            <table class="table table-sm table-borderless">
                                <tr><th width="40%">Full Name</th><td><strong>${escHtml(d.full_name)}</strong></td></tr>
                                <tr><th>Username</th><td><code>${escHtml(d.username)}</code></td></tr>
                                <tr><th>Email</th><td>${escHtml(d.email || '—')}</td></tr>
                                <tr><th>Phone</th><td>${escHtml(d.phone || '—')}</td></tr>
                                <tr><th>Status</th><td>${d.is_active == 1 
                                    ? '<span class="badge bg-success">Active</span>' 
                                    : '<span class="badge bg-secondary">Inactive</span>'}</td></tr>
                                <tr><th>Last Login</th><td>${d.last_login ? formatDate(d.last_login) : '<span class="text-muted">Never</span>'}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="bi bi-award me-2"></i>Professional Details
                            </h6>
                            <table class="table table-sm table-borderless">
                                <tr><th width="40%">Specialization</th><td>${d.specialization 
                                    ? '<span class="badge bg-info text-dark">' + escHtml(d.specialization) + '</span>' 
                                    : '<span class="text-muted">Not set</span>'}</td></tr>
                                <tr><th>Qualification</th><td>${escHtml(d.qualification || '—')}</td></tr>
                                <tr><th>License No.</th><td>${d.license_number ? '<code>' + escHtml(d.license_number) + '</code>' : '—'}</td></tr>
                                <tr><th>Experience</th><td>${d.experience_years ? d.experience_years + ' years' : '—'}</td></tr>
                                <tr><th>Consult. Fee</th><td>${d.consultation_fee > 0 
                                    ? '<strong>Rs. ' + parseFloat(d.consultation_fee).toFixed(2) + '</strong>' 
                                    : '—'}</td></tr>
                            </table>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="bi bi-calendar-week me-2"></i>Schedule
                            </h6>
                            <table class="table table-sm table-borderless">
                                <tr><th width="20%">Available Days</th><td>${days}</td></tr>
                                <tr><th>Working Hours</th><td>${timeStart} — ${timeEnd}</td></tr>
                                ${d.bio ? '<tr><th>Bio</th><td>' + escHtml(d.bio) + '</td></tr>' : ''}
                            </table>
                        </div>
                    </div>
                `;
                
                document.getElementById('viewDoctorBody').innerHTML = html;
                document.getElementById('viewEditBtn').setAttribute('onclick', 
                    'bootstrap.Modal.getInstance(document.getElementById("viewDoctorModal")).hide(); editDoctor(' + id + ');');
            } else {
                document.getElementById('viewDoctorBody').innerHTML = 
                    '<div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i>' + (res.message || 'Failed to load.') + '</div>';
            }
        },
        error: function() {
            document.getElementById('viewDoctorBody').innerHTML = 
                '<div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i>Server error.</div>';
        }
    });
}

// ===== Edit Doctor =====
function editDoctor(id) {
    $.ajax({
        url: '<?php echo APP_URL; ?>/admin/ajax/get_user.php',
        type: 'GET',
        data: { user_id: id, role: 'doctor', details: 1 },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                let d = res.data;
                
                // Basic info
                document.getElementById('doctor_user_id').value = d.user_id;
                document.getElementById('doctor_full_name').value = d.full_name;
                document.getElementById('doctor_username').value = d.username;
                document.getElementById('doctor_email').value = d.email || '';
                document.getElementById('doctor_phone').value = d.phone || '';
                document.getElementById('doctor_status').value = d.is_active;
                
                // Password
                document.getElementById('doctor_password').value = '';
                document.getElementById('doctor_password').removeAttribute('required');
                document.getElementById('doctor_pwd_required').style.display = 'none';
                document.getElementById('doctor_pwd_hint').style.display = 'block';
                
                // Professional details
                document.getElementById('doctor_specialization').value = d.specialization || '';
                document.getElementById('doctor_qualification').value = d.qualification || '';
                document.getElementById('doctor_license').value = d.license_number || '';
                document.getElementById('doctor_experience').value = d.experience_years || 0;
                document.getElementById('doctor_bio').value = d.bio || '';
                
                // Schedule
                document.getElementById('doctor_fee').value = d.consultation_fee || 0;
                document.getElementById('doctor_time_start').value = d.available_time_start || '08:00';
                document.getElementById('doctor_time_end').value = d.available_time_end || '17:00';
                
                // Available days checkboxes
                let availDays = d.available_days ? d.available_days.split(',').map(s => s.trim()) : [];
                ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'].forEach(function(day) {
                    let cb = document.getElementById('day_' + day);
                    if (cb) cb.checked = availDays.includes(day);
                });
                
                // Update modal title
                document.getElementById('doctorModalLabel').innerHTML = '<i class="bi bi-pencil me-2"></i>Edit Doctor';
                
                // Show first tab
                var firstTab = new bootstrap.Tab(document.getElementById('basic-tab'));
                firstTab.show();
                
                var modal = new bootstrap.Modal(document.getElementById('doctorModal'));
                modal.show();
            } else {
                showAlert('doctorAlert', 'danger', res.message || 'Failed to load doctor.');
            }
        },
        error: function() {
            showAlert('doctorAlert', 'danger', 'Server error while loading doctor data.');
        }
    });
}

// ===== Save Doctor =====
$('#doctorForm').on('submit', function(e) {
    e.preventDefault();
    
    // Validate required fields across tabs
    let fullName = document.getElementById('doctor_full_name').value.trim();
    let username = document.getElementById('doctor_username').value.trim();
    let email = document.getElementById('doctor_email').value.trim();
    let specialization = document.getElementById('doctor_specialization').value;
    let qualification = document.getElementById('doctor_qualification').value.trim();
    let userId = document.getElementById('doctor_user_id').value;
    let password = document.getElementById('doctor_password').value;
    
    if (!fullName || !username || !email) {
        var tab1 = new bootstrap.Tab(document.getElementById('basic-tab'));
        tab1.show();
        showAlert('doctorAlert', 'danger', 'Please fill in all required fields in Basic Info tab.');
        return;
    }
    
    if (!specialization || !qualification) {
        var tab2 = new bootstrap.Tab(document.getElementById('professional-tab'));
        tab2.show();
        showAlert('doctorAlert', 'danger', 'Please fill in Specialization and Qualification in Professional Details tab.');
        return;
    }
    
    if (!userId && (!password || password.length < 6)) {
        var tab1 = new bootstrap.Tab(document.getElementById('basic-tab'));
        tab1.show();
        showAlert('doctorAlert', 'danger', 'Password is required (minimum 6 characters) for new doctors.');
        return;
    }
    
    let btn = $('#saveDoctorBtn');
    btn.prop('disabled', true).html('<i class="bi bi-hourglass-split me-1"></i>Saving...');
    
    $.ajax({
        url: '<?php echo APP_URL; ?>/admin/ajax/save_user.php',
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(res) {
            btn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i>Save Doctor');
            if (res.success) {
                showAlert('doctorAlert', 'success', res.message);
                bootstrap.Modal.getInstance(document.getElementById('doctorModal')).hide();
                setTimeout(function() { location.reload(); }, 800);
            } else {
                showAlert('doctorAlert', 'danger', res.message);
            }
        },
        error: function() {
            btn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i>Save Doctor');
            showAlert('doctorAlert', 'danger', 'Server error. Please try again.');
        }
    });
});

// ===== Toggle Status =====
function toggleStatus(userId, newStatus) {
    let action = newStatus === 1 ? 'activate' : 'deactivate';
    if (!confirm('Are you sure you want to ' + action + ' this doctor?')) return;
    
    $.ajax({
        url: '<?php echo APP_URL; ?>/admin/ajax/update_status.php',
        type: 'POST',
        data: { user_id: userId, status: newStatus },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                showAlert('doctorAlert', 'success', res.message);
                setTimeout(function() { location.reload(); }, 600);
            } else {
                showAlert('doctorAlert', 'danger', res.message);
            }
        },
        error: function() {
            showAlert('doctorAlert', 'danger', 'Server error.');
        }
    });
}

// ===== Utility Functions =====
function togglePassword(inputId, btn) {
    let input = document.getElementById(inputId);
    let icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}

function showAlert(containerId, type, message) {
    let icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
    let html = '<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
               '<i class="bi bi-' + icon + ' me-2"></i>' + message +
               '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    document.getElementById(containerId).innerHTML = html;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function escHtml(text) {
    if (!text) return '';
    let div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
}

function formatDate(dateStr) {
    let d = new Date(dateStr);
    let months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return months[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear();
}

function formatTime12(timeStr) {
    if (!timeStr) return '';
    let parts = timeStr.split(':');
    let h = parseInt(parts[0]);
    let m = parts[1];
    let ampm = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12;
    return h + ':' + m + ' ' + ampm;
}

// ===== Auto-open Add modal =====
<?php if ($autoOpenAdd): ?>
$(document).ready(function() {
    resetDoctorForm();
    var modal = new bootstrap.Modal(document.getElementById('doctorModal'));
    modal.show();
});
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>