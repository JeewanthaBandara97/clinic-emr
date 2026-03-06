<?php
/**
 * Assistant Management
 * Clinic EMR System
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

$pageTitle = 'Manage Assistants';

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$user = new User();

// Fetch all assistants
try {
    $assistants = $user->getAllAssistantsAdmin();
} catch (Exception $e) {
    $assistants = [];
}

$activeAssistants = count(array_filter($assistants, fn($a) => (int)$a['is_active'] === 1));
$inactiveAssistants = count($assistants) - $activeAssistants;

// Auto-open add modal
$autoOpenAdd = isset($_GET['action']) && $_GET['action'] === 'add';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-person-badge me-2"></i>Assistant Management</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo APP_URL; ?>/admin/index.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Assistants</li>
                </ol>
            </nav>
        </div>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#assistantModal" onclick="resetAssistantForm()">
            <i class="bi bi-person-plus me-2"></i>Add Assistant
        </button>
    </div>
</div>

<?php displayFlash(); ?>

<div id="assistantAlert"></div>

<!-- Statistics -->
<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="stat-card">
            <div class="stat-icon success">
                <i class="bi bi-person-badge"></i>
            </div>
            <div class="stat-value"><?php echo count($assistants); ?></div>
            <div class="stat-label">Total Assistants</div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="bi bi-check-circle"></i>
            </div>
            <div class="stat-value"><?php echo $activeAssistants; ?></div>
            <div class="stat-label">Active Assistants</div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="bi bi-pause-circle"></i>
            </div>
            <div class="stat-value"><?php echo $inactiveAssistants; ?></div>
            <div class="stat-label">Inactive Assistants</div>
        </div>
    </div>
</div>

<!-- Assistants Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-table me-2"></i>All Assistants</span>
        <span class="badge bg-success"><?php echo count($assistants); ?> total</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($assistants)): ?>
            <div class="text-center py-5">
                <i class="bi bi-person-badge display-4 text-muted"></i>
                <p class="text-muted mt-2">No assistants found</p>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#assistantModal" onclick="resetAssistantForm()">
                    <i class="bi bi-person-plus me-2"></i>Add First Assistant
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Full Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Joined</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assistants as $i => $asst): ?>
                            <tr id="row-<?php echo $asst['user_id']; ?>">
                                <td><?php echo $i + 1; ?></td>
                                <td><strong><?php echo htmlspecialchars($asst['full_name']); ?></strong></td>
                                <td><code><?php echo htmlspecialchars($asst['username']); ?></code></td>
                                <td>
                                    <?php if (!empty($asst['email'])): ?>
                                        <small><?php echo htmlspecialchars($asst['email']); ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">—</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($asst['phone'])): ?>
                                        <small><?php echo htmlspecialchars($asst['phone']); ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">—</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ((int)$asst['is_active'] === 1): ?>
                                        <span class="status-badge status-completed">Active</span>
                                    <?php else: ?>
                                        <span class="status-badge status-waiting">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($asst['last_login'])): ?>
                                        <small><?php echo date('M d, Y', strtotime($asst['last_login'])); ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">Never</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?php echo date('M d, Y', strtotime($asst['created_at'])); ?></small>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" onclick="editAssistant(<?php echo $asst['user_id']; ?>)" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <?php if ((int)$asst['is_active'] === 1): ?>
                                            <button class="btn btn-outline-warning" onclick="toggleStatus(<?php echo $asst['user_id']; ?>, 0)" title="Deactivate">
                                                <i class="bi bi-pause-circle"></i>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-outline-success" onclick="toggleStatus(<?php echo $asst['user_id']; ?>, 1)" title="Activate">
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

<!-- Add/Edit Assistant Modal -->
<div class="modal fade" id="assistantModal" tabindex="-1" aria-labelledby="assistantModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="assistantModalLabel">
                    <i class="bi bi-person-plus me-2"></i>Add Assistant
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="assistantForm">
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="asst_user_id" value="">
                    <input type="hidden" name="role" value="assistant">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="asst_full_name" class="form-label">
                                Full Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="asst_full_name" 
                                   name="full_name" required placeholder="John Smith">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="asst_username" class="form-label">
                                Username <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="asst_username" 
                                   name="username" required placeholder="johnsmith" autocomplete="off">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="asst_email" class="form-label">
                                Email <span class="text-danger">*</span>
                            </label>
                            <input type="email" class="form-control" id="asst_email" 
                                   name="email" required placeholder="assistant@clinic.com">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="asst_phone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="asst_phone" 
                                   name="phone" placeholder="0771234567">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="asst_password" class="form-label">
                                Password <span class="text-danger" id="asst_pwd_required">*</span>
                            </label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="asst_password" 
                                       name="password" autocomplete="new-password" placeholder="Minimum 6 characters">
                                <button class="btn btn-outline-secondary" type="button" 
                                        onclick="togglePassword('asst_password', this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <small class="text-muted" id="asst_pwd_hint" style="display:none;">
                                Leave blank to keep current password.
                            </small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="asst_status" class="form-label">Status</label>
                            <select class="form-select" id="asst_status" name="is_active">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-success" id="saveAssistantBtn">
                        <i class="bi bi-check-circle me-1"></i>Save Assistant
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function resetAssistantForm() {
    document.getElementById('assistantForm').reset();
    document.getElementById('asst_user_id').value = '';
    document.getElementById('assistantModalLabel').innerHTML = '<i class="bi bi-person-plus me-2"></i>Add Assistant';
    document.getElementById('asst_pwd_required').style.display = 'inline';
    document.getElementById('asst_pwd_hint').style.display = 'none';
    document.getElementById('asst_password').setAttribute('required', 'required');
}

function editAssistant(id) {
    $.ajax({
        url: '<?php echo APP_URL; ?>/admin/ajax/get_user.php',
        type: 'GET',
        data: { user_id: id, role: 'assistant' },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                let d = res.data;
                document.getElementById('asst_user_id').value = d.user_id;
                document.getElementById('asst_full_name').value = d.full_name;
                document.getElementById('asst_username').value = d.username;
                document.getElementById('asst_email').value = d.email || '';
                document.getElementById('asst_phone').value = d.phone || '';
                document.getElementById('asst_status').value = d.is_active;
                document.getElementById('asst_password').value = '';
                document.getElementById('asst_password').removeAttribute('required');
                document.getElementById('asst_pwd_required').style.display = 'none';
                document.getElementById('asst_pwd_hint').style.display = 'block';
                document.getElementById('assistantModalLabel').innerHTML = '<i class="bi bi-pencil me-2"></i>Edit Assistant';
                
                var modal = new bootstrap.Modal(document.getElementById('assistantModal'));
                modal.show();
            } else {
                showAlert('assistantAlert', 'danger', res.message || 'Failed to load assistant.');
            }
        },
        error: function() {
            showAlert('assistantAlert', 'danger', 'Server error.');
        }
    });
}

$('#assistantForm').on('submit', function(e) {
    e.preventDefault();
    
    let btn = $('#saveAssistantBtn');
    btn.prop('disabled', true).html('<i class="bi bi-hourglass-split me-1"></i>Saving...');
    
    $.ajax({
        url: '<?php echo APP_URL; ?>/admin/ajax/save_user.php',
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(res) {
            btn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i>Save Assistant');
            if (res.success) {
                showAlert('assistantAlert', 'success', res.message);
                bootstrap.Modal.getInstance(document.getElementById('assistantModal')).hide();
                setTimeout(function() { location.reload(); }, 800);
            } else {
                showAlert('assistantAlert', 'danger', res.message);
            }
        },
        error: function() {
            btn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i>Save Assistant');
            showAlert('assistantAlert', 'danger', 'Server error.');
        }
    });
});

function toggleStatus(userId, newStatus) {
    let action = newStatus === 1 ? 'activate' : 'deactivate';
    if (!confirm('Are you sure you want to ' + action + ' this assistant?')) return;
    
    $.ajax({
        url: '<?php echo APP_URL; ?>/admin/ajax/update_status.php',
        type: 'POST',
        data: { user_id: userId, status: newStatus },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                showAlert('assistantAlert', 'success', res.message);
                setTimeout(function() { location.reload(); }, 600);
            } else {
                showAlert('assistantAlert', 'danger', res.message);
            }
        },
        error: function() {
            showAlert('assistantAlert', 'danger', 'Server error.');
        }
    });
}

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

<?php if ($autoOpenAdd): ?>
$(document).ready(function() {
    resetAssistantForm();
    var modal = new bootstrap.Modal(document.getElementById('assistantModal'));
    modal.show();
});
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>