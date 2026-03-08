<?php
/**
 * Add New Medicine
 */

$pageTitle = 'Add Medicine';

require_once __DIR__ . '/../includes/auth.php';
requireAssistant();

require_once __DIR__ . '/../classes/Medicine.php';

$medicineObj = new Medicine();

// Get lookup data
$mainCategories = $medicineObj->getMainCategories();
$genericNames = $medicineObj->getGenericNames();
$tradeNames = $medicineObj->getTradeNames();
$issuingUnits = $medicineObj->getIssuingUnits();
$strengthUnits = $medicineObj->getStrengthUnits();

$errors = [];
$formData = [
    'medicine_name' => '',
    'main_category_id' => '',
    'sub_category1_id' => '',
    'sub_category2_id' => '',
    'generic_id' => '',
    'trade_id' => '',
    'strength_value' => '',
    'strength_unit_id' => '',
    'issuing_unit_id' => '',
    'mrp' => '',
    'dosage_form' => '',
    'route' => '',
    'instructions' => '',
    'reorder_level' => 10,
    'is_expiry_tracked' => 1,
    'discount_enabled' => 0
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCSRF();
    
    // Get form data
    foreach ($formData as $key => $default) {
        $formData[$key] = $_POST[$key] ?? $default;
    }
    $formData['medicine_name'] = sanitize($formData['medicine_name']);
    $formData['instructions'] = sanitize($formData['instructions']);
    $formData['is_expiry_tracked'] = isset($_POST['is_expiry_tracked']) ? 1 : 0;
    $formData['discount_enabled'] = isset($_POST['discount_enabled']) ? 1 : 0;
    
    // Validation
    if (empty($formData['medicine_name'])) {
        $errors[] = 'Medicine name is required.';
    }
    
    // Create medicine
    if (empty($errors)) {
        try {
            $formData['medicine_code'] = $medicineObj->generateMedicineCode();
            $medicineId = $medicineObj->create($formData);
            
            if ($medicineId) {
                redirect(APP_URL . '/assistant/medicines.php', 'success', 
                    'Medicine added successfully! Code: ' . $formData['medicine_code']);
            } else {
                $errors[] = 'Failed to add medicine.';
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
    <h1><i class="bi bi-plus-circle me-2"></i>Add New Medicine</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="medicines.php">Medicines</a></li>
            <li class="breadcrumb-item active">Add New</li>
        </ol>
    </nav>
</div>

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
                                   value="<?php echo htmlspecialchars($formData['medicine_name']); ?>" 
                                   placeholder="e.g., Paracetamol 500mg Tablets" required>
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
                                        <?php if ($tn['manufacturer']): ?> 
                                            (<?php echo htmlspecialchars($tn['manufacturer']); ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Strength Value</label>
                            <input type="text" class="form-control" name="strength_value" 
                                   value="<?php echo htmlspecialchars($formData['strength_value']); ?>"
                                   placeholder="e.g., 500">
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
                                        <?php echo $formData['dosage_form'] === $form ? 'selected' : ''; ?>>
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
                                        <?php echo $formData['route'] === $route ? 'selected' : ''; ?>>
                                        <?php echo $route; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">MRP (Rs.)</label>
                            <input type="number" step="0.01" class="form-control" name="mrp" 
                                   value="<?php echo htmlspecialchars($formData['mrp']); ?>"
                                   placeholder="0.00" min="0">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Instructions</label>
                        <textarea class="form-control" name="instructions" rows="2" 
                                  placeholder="Usage instructions, warnings, etc."><?php echo htmlspecialchars($formData['instructions']); ?></textarea>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Category & Settings -->
        <div class="col-lg-4 mb-4">
            <!-- Categories -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-folder me-2"></i>Categories
                </div>
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
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Sub Category 2</label>
                        <select class="form-select" name="sub_category2_id" id="subCategory2">
                            <option value="">Select Sub Category</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Settings -->
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-gear me-2"></i>Settings
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Reorder Level</label>
                        <input type="number" class="form-control" name="reorder_level" 
                               value="<?php echo htmlspecialchars($formData['reorder_level']); ?>" min="0">
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="is_expiry_tracked" 
                               id="isExpiryTracked" <?php echo $formData['is_expiry_tracked'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="isExpiryTracked">
                            Track Expiry Date
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="discount_enabled" 
                               id="discountEnabled" <?php echo $formData['discount_enabled'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="discountEnabled">
                            Discount Enabled
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Submit Buttons -->
    <div class="card">
        <div class="card-body d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="bi bi-check-circle me-2"></i>Add Medicine
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
    
    // Load sub categories when main category changes
    mainCategory.addEventListener('change', function() {
        const parentId = this.value;
        subCategory1.innerHTML = '<option value="">Loading...</option>';
        subCategory2.innerHTML = '<option value="">Select Sub Category</option>';
        
        if (!parentId) {
            subCategory1.innerHTML = '<option value="">Select Sub Category</option>';
            return;
        }
        
        fetch('ajax/get-subcategories.php?parent_id=' + parentId)
            .then(response => response.json())
            .then(data => {
                let html = '<option value="">Select Sub Category</option>';
                data.forEach(cat => {
                    html += `<option value="${cat.category_id}">${cat.category_name}</option>`;
                });
                subCategory1.innerHTML = html;
            })
            .catch(() => {
                subCategory1.innerHTML = '<option value="">Error loading</option>';
            });
    });
    
    // Load sub categories 2 when sub category 1 changes
    subCategory1.addEventListener('change', function() {
        const parentId = this.value;
        subCategory2.innerHTML = '<option value="">Loading...</option>';
        
        if (!parentId) {
            subCategory2.innerHTML = '<option value="">Select Sub Category</option>';
            return;
        }
        
        fetch('ajax/get-subcategories.php?parent_id=' + parentId)
            .then(response => response.json())
            .then(data => {
                let html = '<option value="">Select Sub Category</option>';
                data.forEach(cat => {
                    html += `<option value="${cat.category_id}">${cat.category_name}</option>`;
                });
                subCategory2.innerHTML = html;
            })
            .catch(() => {
                subCategory2.innerHTML = '<option value="">Error loading</option>';
            });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>