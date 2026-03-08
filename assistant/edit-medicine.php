<?php
/**
 * Edit Medicine
 */

require_once __DIR__ . '/../includes/auth.php';
requireAssistant();
require_once __DIR__ . '/../includes/functions.php';   // ADD THIS
require_once __DIR__ . '/../includes/csrf.php';   // ADD THIS
require_once __DIR__ . '/../classes/Medicine.php';

$medicineId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($medicineId <= 0) {
    redirect(APP_URL . '/assistant/medicines.php', 'danger', 'Invalid medicine ID.');
}

$medicineObj = new Medicine();
$medicine = $medicineObj->getById($medicineId);

if (!$medicine) {
    redirect(APP_URL . '/assistant/medicines.php', 'danger', 'Medicine not found.');
}

// Get lookup data
$mainCategories = $medicineObj->getMainCategories();
$genericNames = $medicineObj->getGenericNames();
$tradeNames = $medicineObj->getTradeNames();
$issuingUnits = $medicineObj->getIssuingUnits();
$strengthUnits = $medicineObj->getStrengthUnits();

// Get sub categories for preselection
$subCategories1 = $medicine['main_category_id'] ? $medicineObj->getSubCategories($medicine['main_category_id']) : [];
$subCategories2 = $medicine['sub_category1_id'] ? $medicineObj->getSubCategories($medicine['sub_category1_id']) : [];

$errors = [];
$formData = $medicine;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCSRF();
    
    $fields = ['medicine_name', 'main_category_id', 'sub_category1_id', 'sub_category2_id', 
               'generic_id', 'trade_id', 'strength_value', 'strength_unit_id', 'issuing_unit_id',
               'mrp', 'dosage_form', 'route', 'instructions', 'reorder_level'];
    
    foreach ($fields as $field) {
        $formData[$field] = $_POST[$field] ?? $formData[$field] ?? '';
    }
    $formData['medicine_name'] = sanitize($formData['medicine_name']);
    $formData['instructions'] = sanitize($formData['instructions'] ?? '');
    $formData['is_expiry_tracked'] = isset($_POST['is_expiry_tracked']) ? 1 : 0;
    $formData['discount_enabled'] = isset($_POST['discount_enabled']) ? 1 : 0;
    $formData['is_active'] = isset($_POST['is_active']) ? 1 : 0;
    
    // Validation
    if (empty($formData['medicine_name'])) {
        $errors[] = 'Medicine name is required.';
    }
    
    // Update medicine
    if (empty($errors)) {
        try {
            $updated = $medicineObj->update($medicineId, $formData);
            
            if ($updated) {
                redirect(APP_URL . '/assistant/medicines.php', 'success', 'Medicine updated successfully!');
            } else {
                $errors[] = 'Failed to update medicine.';
            }
        } catch (Exception $e) {
            $errors[] = 'Error: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Edit Medicine: ' . $medicine['medicine_name'];

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h1><i class="bi bi-pencil-square me-2"></i>Edit Medicine</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="medicines.php">Medicines</a></li>
            <li class="breadcrumb-item active">Edit</li>
        </ol>
    </nav>
</div>

<!-- Medicine Info Banner -->
<div class="alert alert-info d-flex align-items-center mb-4">
    <i class="bi bi-capsule fs-4 me-3"></i>
    <div>
        <strong><?php echo htmlspecialchars($medicine['medicine_code']); ?></strong> - 
        <?php echo htmlspecialchars($medicine['medicine_name']); ?>
        <br>
        <small class="text-muted">Last updated: <?php echo formatDateTime($medicine['updated_at']); ?></small>
    </div>
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

<form method="POST" action="">
    <?php echo csrfField(); ?>
    
    <div class="row">
        <!-- Basic Information -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-info-circle me-2"></i>Medicine Information
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label class="form-label">Medicine Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-lg" name="medicine_name" 
                                   value="<?php echo htmlspecialchars($formData['medicine_name']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Generic Name</label>
                            <select class="form-select" name="generic_id">
                                <option value="">Select Generic Name</option>
                                <?php foreach ($genericNames as $gn): ?>
                                    <option value="<?php echo $gn['generic_id']; ?>" 
                                        <?php echo $formData['generic_id'] == $gn['generic_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($gn['generic_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Trade/Brand Name</label>
                            <select class="form-select" name="trade_id">
                                <option value="">Select Trade Name</option>
                                <?php foreach ($tradeNames as $tn): ?>
                                    <option value="<?php echo $tn['trade_id']; ?>"
                                        <?php echo $formData['trade_id'] == $tn['trade_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($tn['trade_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Strength Value</label>
                            <input type="text" class="form-control" name="strength_value" 
                                   value="<?php echo htmlspecialchars($formData['strength_value'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Strength Unit</label>
                            <select class="form-select" name="strength_unit_id">
                                <option value="">Select Unit</option>
                                <?php foreach ($strengthUnits as $su): ?>
                                    <option value="<?php echo $su['strength_unit_id']; ?>"
                                        <?php echo $formData['strength_unit_id'] == $su['strength_unit_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($su['unit_name']); ?> 
                                        (<?php echo htmlspecialchars($su['unit_symbol']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Issuing Unit</label>
                            <select class="form-select" name="issuing_unit_id">
                                <option value="">Select Unit</option>
                                <?php foreach ($issuingUnits as $iu): ?>
                                    <option value="<?php echo $iu['unit_id']; ?>"
                                        <?php echo $formData['issuing_unit_id'] == $iu['unit_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($iu['unit_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Dosage Form</label>
                            <select class="form-select" name="dosage_form">
                                <option value="">Select Form</option>
                                <?php foreach (['Tablet', 'Capsule', 'Syrup', 'Injection', 'Cream', 'Ointment', 'Drops', 'Inhaler', 'Gel', 'Powder', 'Suspension', 'Solution'] as $form): ?>
                                    <option value="<?php echo $form; ?>"
                                        <?php echo ($formData['dosage_form'] ?? '') === $form ? 'selected' : ''; ?>>
                                        <?php echo $form; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Route</label>
                            <select class="form-select" name="route">
                                <option value="">Select Route</option>
                                <?php foreach (['Oral', 'Topical', 'Injection', 'Inhalation', 'Sublingual', 'Rectal', 'Ophthalmic', 'Otic', 'Nasal', 'Transdermal'] as $route): ?>
                                    <option value="<?php echo $route; ?>"
                                        <?php echo ($formData['route'] ?? '') === $route ? 'selected' : ''; ?>>
                                        <?php echo $route; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">MRP (Rs.)</label>
                            <input type="number" step="0.01" class="form-control" name="mrp" 
                                   value="<?php echo htmlspecialchars($formData['mrp'] ?? ''); ?>" min="0">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Instructions</label>
                        <textarea class="form-control" name="instructions" rows="2"><?php echo htmlspecialchars($formData['instructions'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Category & Settings -->
        <div class="col-lg-4 mb-4">
            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-folder me-2"></i>Categories</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Main Category</label>
                        <select class="form-select" name="main_category_id" id="mainCategory">
                            <option value="">Select Category</option>
                            <?php foreach ($mainCategories as $cat): ?>
                                <option value="<?php echo $cat['category_id']; ?>"
                                    <?php echo $formData['main_category_id'] == $cat['category_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sub Category 1</label>
                        <select class="form-select" name="sub_category1_id" id="subCategory1">
                            <option value="">Select Sub Category</option>
                            <?php foreach ($subCategories1 as $cat): ?>
                                <option value="<?php echo $cat['category_id']; ?>"
                                    <?php echo $formData['sub_category1_id'] == $cat['category_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Sub Category 2</label>
                        <select class="form-select" name="sub_category2_id" id="subCategory2">
                            <option value="">Select Sub Category</option>
                            <?php foreach ($subCategories2 as $cat): ?>
                                <option value="<?php echo $cat['category_id']; ?>"
                                    <?php echo $formData['sub_category2_id'] == $cat['category_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header"><i class="bi bi-gear me-2"></i>Settings</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Reorder Level</label>
                        <input type="number" class="form-control" name="reorder_level" 
                               value="<?php echo htmlspecialchars($formData['reorder_level'] ?? 10); ?>" min="0">
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="is_expiry_tracked" 
                               id="isExpiryTracked" <?php echo ($formData['is_expiry_tracked'] ?? 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="isExpiryTracked">Track Expiry Date</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="discount_enabled" 
                               id="discountEnabled" <?php echo ($formData['discount_enabled'] ?? 0) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="discountEnabled">Discount Enabled</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" 
                               id="isActive" <?php echo ($formData['is_active'] ?? 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="isActive">Active</label>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body d-flex gap-2">
            <button type="submit" class="btn btn-success btn-lg">
                <i class="bi bi-check-circle me-2"></i>Update Medicine
            </button>
            <a href="medicines.php" class="btn btn-secondary btn-lg">
                <i class="bi bi-x-circle me-2"></i>Cancel
            </a>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mainCategory = document.getElementById('mainCategory');
    const subCategory1 = document.getElementById('subCategory1');
    const subCategory2 = document.getElementById('subCategory2');
    
    mainCategory.addEventListener('change', function() {
        const parentId = this.value;
        subCategory1.innerHTML = '<option value="">Loading...</option>';
        subCategory2.innerHTML = '<option value="">Select Sub Category</option>';
        
        if (!parentId) {
            subCategory1.innerHTML = '<option value="">Select Sub Category</option>';
            return;
        }
        
        fetch('ajax/get-subcategories.php?parent_id=' + parentId)
            .then(r => r.json())
            .then(data => {
                let html = '<option value="">Select Sub Category</option>';
                data.forEach(cat => html += `<option value="${cat.category_id}">${cat.category_name}</option>`);
                subCategory1.innerHTML = html;
            });
    });
    
    subCategory1.addEventListener('change', function() {
        const parentId = this.value;
        subCategory2.innerHTML = '<option value="">Loading...</option>';
        
        if (!parentId) {
            subCategory2.innerHTML = '<option value="">Select Sub Category</option>';
            return;
        }
        
        fetch('ajax/get-subcategories.php?parent_id=' + parentId)
            .then(r => r.json())
            .then(data => {
                let html = '<option value="">Select Sub Category</option>';
                data.forEach(cat => html += `<option value="${cat.category_id}">${cat.category_name}</option>`);
                subCategory2.innerHTML = html;
            });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>