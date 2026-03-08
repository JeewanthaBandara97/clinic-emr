<?php
/**
 * Lab Test Management
 * Manage Test Types and Lab Tests
 */

$pageTitle = 'Lab Test Management';

require_once __DIR__ . '/../includes/auth.php';
requireAssistant();

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../classes/LabTest.php';

$labTest = new LabTest();

// =====================================================
// HANDLE FORM SUBMISSIONS
// =====================================================

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    checkCSRF();
    
    $action = $_POST['action'];
    
    try {
        switch ($action) {
            
            // =====================================================
            // TEST TYPE ACTIONS
            // =====================================================
            case 'add_type':
                $name = trim($_POST['type_name'] ?? '');
                $displayOrder = (int)($_POST['display_order'] ?? 0);
                
                if (empty($name)) {
                    throw new Exception('Test type name is required.');
                }
                
                $labTest->addTestType($name, $displayOrder);
                $message = 'Test type added successfully.';
                $messageType = 'success';
                break;
                
            case 'edit_type':
                $typeId = (int)($_POST['type_id'] ?? 0);
                $name = trim($_POST['type_name'] ?? '');
                $displayOrder = (int)($_POST['display_order'] ?? 0);
                
                if (empty($name)) {
                    throw new Exception('Test type name is required.');
                }
                
                $labTest->updateTestType($typeId, $name, $displayOrder);
                $message = 'Test type updated successfully.';
                $messageType = 'success';
                break;
                
            case 'delete_type':
                $typeId = (int)($_POST['type_id'] ?? 0);
                $labTest->deleteTestType($typeId);
                $message = 'Test type deleted successfully.';
                $messageType = 'success';
                break;
            
            // =====================================================
            // LAB TEST ACTIONS
            // =====================================================
            case 'add_test':
                $typeId = (int)($_POST['type_id'] ?? 0);
                $name = trim($_POST['test_name'] ?? '');
                $price = (float)($_POST['test_price'] ?? 0);
                
                if (empty($name)) {
                    throw new Exception('Test name is required.');
                }
                if ($typeId <= 0) {
                    throw new Exception('Please select a test type.');
                }
                
                $labTest->addLabTest($typeId, $name, $price);
                $message = 'Lab test added successfully.';
                $messageType = 'success';
                break;
                
            case 'edit_test':
                $testId = (int)($_POST['test_id'] ?? 0);
                
                $labTest->updateLabTest($testId, [
                    'type_id' => $_POST['type_id'] ?? 0,
                    'test_name' => $_POST['test_name'] ?? '',
                    'test_price' => $_POST['test_price'] ?? 0,
                    'is_active' => isset($_POST['is_active']) ? 1 : 0
                ]);
                $message = 'Lab test updated successfully.';
                $messageType = 'success';
                break;
                
            case 'delete_test':
                $testId = (int)($_POST['test_id'] ?? 0);
                $labTest->deleteLabTest($testId);
                $message = 'Lab test deleted successfully.';
                $messageType = 'success';
                break;
                
            case 'toggle_test_status':
                $testId = (int)($_POST['test_id'] ?? 0);
                $status = (int)($_POST['status'] ?? 0);
                $labTest->toggleLabTestStatus($testId, $status);
                $message = 'Test status updated successfully.';
                $messageType = 'success';
                break;
                
            case 'bulk_price_update':
                $percentage = (float)($_POST['percentage'] ?? 0);
                $typeId = !empty($_POST['type_id']) ? (int)$_POST['type_id'] : null;
                
                if ($percentage == 0) {
                    throw new Exception('Please enter a valid percentage.');
                }
                
                $affected = $labTest->bulkUpdatePrices($percentage, $typeId);
                $message = "Prices updated for {$affected} test(s).";
                $messageType = 'success';
                break;
        }
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'danger';
    }
}

// =====================================================
// GET ALL DATA
// =====================================================

// Test Types with test count
$testTypes = $labTest->getTestTypes();

// Type list for dropdowns
$typeList = $labTest->getTypeList();

// Statistics
$stats = $labTest->getStats();

// Get current tab
$activeTab = $_GET['tab'] ?? 'tests';

// Search/Filter
$filterType = $_GET['type'] ?? '';
$searchTerm = trim($_GET['search'] ?? '');

// Get lab tests with filters
if (!empty($searchTerm)) {
    $labTests = $labTest->search($searchTerm, 100, false);
    if (!empty($filterType)) {
        $labTests = array_filter($labTests, fn($t) => $t['type_id'] == $filterType);
    }
} else {
    $labTests = $labTest->getLabTests(false, $filterType ?: null);
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<!-- =====================================================
     PAGE HEADER
     ===================================================== -->
<div class="page-header d-flex justify-content-between align-items-center flex-wrap">
    <div>
        <h1>
            <i class="bi bi-clipboard2-pulse me-2"></i>
            Lab Test Management
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="index.php">Dashboard</a>
                </li>
                <li class="breadcrumb-item active">Lab Tests</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <button type="button" 
                class="btn btn-outline-warning" 
                data-bs-toggle="modal" 
                data-bs-target="#bulkPriceModal">
            <i class="bi bi-currency-rupee me-1"></i>
            Bulk Price Update
        </button>
        <button type="button" 
                class="btn btn-outline-primary" 
                data-bs-toggle="modal" 
                data-bs-target="#typeModal"
                onclick="resetTypeModal()">
            <i class="bi bi-folder-plus me-1"></i>
            Add Test Type
        </button>
        <button type="button" 
                class="btn btn-primary" 
                data-bs-toggle="modal" 
                data-bs-target="#testModal"
                onclick="resetTestModal()">
            <i class="bi bi-plus-circle me-1"></i>
            Add Lab Test
        </button>
    </div>
</div>

<!-- =====================================================
     ALERT MESSAGE
     ===================================================== -->
<?php if (!empty($message)): ?>
<div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
    <i class="bi bi-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
    <?php echo htmlspecialchars($message); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- =====================================================
     STATISTICS CARDS
     ===================================================== -->
<div class="row mb-4">
    <div class="col-6 col-lg-2 mb-2">
        <div class="card bg-primary text-white h-100">
            <div class="card-body text-center py-3">
                <h3 class="mb-0"><?php echo $stats['total_types']; ?></h3>
                <small>Test Types</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-2 mb-2">
        <div class="card bg-info text-white h-100">
            <div class="card-body text-center py-3">
                <h3 class="mb-0"><?php echo $stats['total_tests']; ?></h3>
                <small>Total Tests</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-2 mb-2">
        <div class="card bg-success text-white h-100">
            <div class="card-body text-center py-3">
                <h3 class="mb-0"><?php echo $stats['active_tests']; ?></h3>
                <small>Active Tests</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-2 mb-2">
        <div class="card bg-secondary text-white h-100">
            <div class="card-body text-center py-3">
                <h3 class="mb-0"><?php echo $stats['inactive_tests']; ?></h3>
                <small>Inactive</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-2 mb-2">
        <div class="card bg-warning text-dark h-100">
            <div class="card-body text-center py-3">
                <h3 class="mb-0">RS: <?php echo number_format($stats['avg_price']); ?></h3>
                <small>Avg Price</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-2 mb-2">
        <div class="card bg-dark text-white h-100">
            <div class="card-body text-center py-3">
                <h3 class="mb-0">RS: <?php echo number_format($stats['max_price']); ?></h3>
                <small>Max Price</small>
            </div>
        </div>
    </div>
</div>

<!-- =====================================================
     TAB NAVIGATION
     ===================================================== -->
<div class="card">
    <div class="card-header p-0">
        <ul class="nav nav-tabs card-header-tabs" id="labTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $activeTab === 'tests' ? 'active' : ''; ?>" 
                        id="tests-tab" 
                        data-bs-toggle="tab" 
                        data-bs-target="#tests" 
                        type="button" 
                        role="tab">
                    <i class="bi bi-list-ul me-1"></i>
                    Lab Tests
                    <span class="badge bg-primary ms-1"><?php echo $stats['total_tests']; ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $activeTab === 'types' ? 'active' : ''; ?>" 
                        id="types-tab" 
                        data-bs-toggle="tab" 
                        data-bs-target="#types" 
                        type="button" 
                        role="tab">
                    <i class="bi bi-folder me-1"></i>
                    Test Types
                    <span class="badge bg-secondary ms-1"><?php echo $stats['total_types']; ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $activeTab === 'grouped' ? 'active' : ''; ?>" 
                        id="grouped-tab" 
                        data-bs-toggle="tab" 
                        data-bs-target="#grouped" 
                        type="button" 
                        role="tab">
                    <i class="bi bi-diagram-3 me-1"></i>
                    Grouped View
                </button>
            </li>
        </ul>
    </div>

    <div class="card-body">
        <div class="tab-content" id="labTabsContent">
            
            <!-- =====================================================
                 TAB 1: LAB TESTS
                 ===================================================== -->
            <div class="tab-pane fade <?php echo $activeTab === 'tests' ? 'show active' : ''; ?>" 
                 id="tests" 
                 role="tabpanel">
                
                <!-- Search & Filter -->
                <div class="row mb-3">
                    <div class="col-md-8">
                        <form method="GET" action="" class="d-flex gap-2">
                            <input type="hidden" name="tab" value="tests">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" 
                                       class="form-control" 
                                       name="search" 
                                       placeholder="Search tests..."
                                       value="<?php echo htmlspecialchars($searchTerm); ?>">
                            </div>
                            <select class="form-select" name="type" style="max-width: 200px;">
                                <option value="">All Types</option>
                                <?php foreach ($typeList as $type): ?>
                                <option value="<?php echo $type['type_id']; ?>" 
                                        <?php echo $filterType == $type['type_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type['type_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-funnel"></i>
                            </button>
                            <?php if (!empty($filterType) || !empty($searchTerm)): ?>
                            <a href="?tab=tests" class="btn btn-outline-secondary">
                                <i class="bi bi-x-lg"></i>
                            </a>
                            <?php endif; ?>
                        </form>
                    </div>
                    <div class="col-md-4 text-end">
                        <button type="button" 
                                class="btn btn-success" 
                                data-bs-toggle="modal" 
                                data-bs-target="#testModal"
                                onclick="resetTestModal()">
                            <i class="bi bi-plus-circle me-1"></i>
                            Add New Test
                        </button>
                    </div>
                </div>
                
                <!-- Tests Table -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="testsTable">
                        <thead class="table-light">
                            <tr>
                                <th width="50">#</th>
                                <th>Test Name</th>
                                <th>Test Type</th>
                                <th class="text-end">Price</th>
                                <th class="text-center">Status</th>
                                <th width="150" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $testIndex = 0;
                            foreach ($labTests as $test): 
                                $testIndex++;
                            ?>
                            <tr class="<?php echo $test['is_active'] ? '' : 'table-secondary'; ?>">
                                <td><?php echo $testIndex; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($test['test_name']); ?></strong>
                                </td>
                                <td>
                                    <span class="badge bg-primary">
                                        <?php echo htmlspecialchars($test['type_name']); ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <strong class="text-success">
                                        RS: <?php echo number_format($test['test_price'], 2); ?>
                                    </strong>
                                </td>
                                <td class="text-center">
                                    <?php if ($test['is_active']): ?>
                                        <span class="badge bg-success">
                                            <i class="bi bi-check-circle me-1"></i>Active
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">
                                            <i class="bi bi-x-circle me-1"></i>Inactive
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" 
                                                onclick="editTest(<?php echo htmlspecialchars(json_encode($test)); ?>)"
                                                title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-outline-<?php echo $test['is_active'] ? 'warning' : 'success'; ?>" 
                                                onclick="toggleTestStatus(<?php echo $test['test_id']; ?>, <?php echo $test['is_active'] ? 0 : 1; ?>, '<?php echo htmlspecialchars(addslashes($test['test_name'])); ?>')"
                                                title="<?php echo $test['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                            <i class="bi bi-<?php echo $test['is_active'] ? 'pause' : 'play'; ?>"></i>
                                        </button>
                                        <button class="btn btn-outline-danger" 
                                                onclick="deleteTest(<?php echo $test['test_id']; ?>, '<?php echo htmlspecialchars(addslashes($test['test_name'])); ?>')"
                                                title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($labTests)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox display-6 d-block mb-2"></i>
                                    <?php if (!empty($searchTerm) || !empty($filterType)): ?>
                                        No tests found matching your criteria.
                                        <br>
                                        <a href="?tab=tests" class="btn btn-sm btn-outline-primary mt-2">Clear Filters</a>
                                    <?php else: ?>
                                        No lab tests found. Add your first test!
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- =====================================================
                 TAB 2: TEST TYPES
                 ===================================================== -->
            <div class="tab-pane fade <?php echo $activeTab === 'types' ? 'show active' : ''; ?>" 
                 id="types" 
                 role="tabpanel">
                
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">
                        <i class="bi bi-folder me-2"></i>
                        Test Types / Categories
                    </h5>
                    <button type="button" 
                            class="btn btn-primary btn-sm" 
                            data-bs-toggle="modal" 
                            data-bs-target="#typeModal"
                            onclick="resetTypeModal()">
                        <i class="bi bi-plus-circle me-1"></i>
                        Add Test Type
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="80">Order</th>
                                <th>Type Name</th>
                                <th class="text-center">Total Tests</th>
                                <th class="text-center">Active Tests</th>
                                <th class="text-center">Status</th>
                                <th width="120" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($testTypes as $type): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <?php echo $type['display_order']; ?>
                                    </span>
                                </td>
                                <td>
                                    <strong>
                                        <i class="bi bi-folder-fill text-warning me-2"></i>
                                        <?php echo htmlspecialchars($type['type_name']); ?>
                                    </strong>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info">
                                        <?php echo $type['test_count']; ?> tests
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success">
                                        <?php echo $type['active_tests'] ?? 0; ?> active
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if ($type['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="editType(<?php echo htmlspecialchars(json_encode($type)); ?>)"
                                            title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" 
                                            onclick="deleteType(<?php echo $type['type_id']; ?>, '<?php echo htmlspecialchars(addslashes($type['type_name'])); ?>', <?php echo $type['test_count']; ?>)"
                                            title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($testTypes)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox display-6 d-block mb-2"></i>
                                    No test types found. Add your first type!
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="alert alert-info mt-3">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Tip:</strong> Use display order to arrange test types in your preferred sequence. 
                    Lower numbers appear first.
                </div>
            </div>

            <!-- =====================================================
                 TAB 3: GROUPED VIEW
                 ===================================================== -->
            <div class="tab-pane fade <?php echo $activeTab === 'grouped' ? 'show active' : ''; ?>" 
                 id="grouped" 
                 role="tabpanel">
                
                <h5 class="mb-3">
                    <i class="bi bi-diagram-3 me-2"></i>
                    Tests Grouped by Type
                </h5>
                
                <div class="accordion" id="testAccordion">
                    <?php 
                    $accordionIndex = 0;
                    foreach ($testTypes as $type): 
                        $accordionIndex++;
                        $typeTests = array_filter($labTests, fn($t) => $t['type_id'] == $type['type_id']);
                    ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading<?php echo $accordionIndex; ?>">
                            <button class="accordion-button <?php echo $accordionIndex > 1 ? 'collapsed' : ''; ?>" 
                                    type="button" 
                                    data-bs-toggle="collapse" 
                                    data-bs-target="#collapse<?php echo $accordionIndex; ?>">
                                <i class="bi bi-folder-fill text-warning me-2"></i>
                                <strong><?php echo htmlspecialchars($type['type_name']); ?></strong>
                                <span class="badge bg-primary ms-2"><?php echo $type['test_count']; ?> tests</span>
                            </button>
                        </h2>
                        <div id="collapse<?php echo $accordionIndex; ?>" 
                             class="accordion-collapse collapse <?php echo $accordionIndex === 1 ? 'show' : ''; ?>" 
                             data-bs-parent="#testAccordion">
                            <div class="accordion-body p-0">
                                <?php if (!empty($typeTests)): ?>
                                <table class="table table-sm mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Test Name</th>
                                            <th class="text-end" width="120">Price</th>
                                            <th class="text-center" width="100">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($typeTests as $test): ?>
                                        <tr class="<?php echo $test['is_active'] ? '' : 'table-secondary'; ?>">
                                            <td><?php echo htmlspecialchars($test['test_name']); ?></td>
                                            <td class="text-end text-success">
                                                RS: <?php echo number_format($test['test_price'], 2); ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($test['is_active']): ?>
                                                    <i class="bi bi-check-circle text-success"></i>
                                                <?php else: ?>
                                                    <i class="bi bi-x-circle text-secondary"></i>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <?php else: ?>
                                <div class="p-3 text-center text-muted">
                                    No tests under this type.
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (empty($testTypes)): ?>
                <div class="text-center text-muted py-4">
                    <i class="bi bi-inbox display-6 d-block mb-2"></i>
                    No test types found.
                </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<!-- =====================================================
     MODAL: ADD/EDIT TEST TYPE
     ===================================================== -->
<div class="modal fade" id="typeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="typeForm">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" id="typeAction" value="add_type">
                <input type="hidden" name="type_id" id="typeId" value="">
                
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="typeModalTitle">
                        <i class="bi bi-folder-plus me-2"></i>
                        Add Test Type
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="typeName" class="form-label">
                            Type Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="typeName" 
                               name="type_name" 
                               required 
                               maxlength="50"
                               placeholder="e.g., Blood Test, Urine Test, X-Ray">
                    </div>
                    
                    <div class="mb-3">
                        <label for="displayOrder" class="form-label">Display Order</label>
                        <input type="number" 
                               class="form-control" 
                               id="displayOrder" 
                               name="display_order" 
                               value="0"
                               min="0"
                               max="999">
                        <div class="form-text">Lower numbers will be displayed first.</div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>
                        <span id="typeSubmitText">Add Type</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- =====================================================
     MODAL: ADD/EDIT LAB TEST
     ===================================================== -->
<div class="modal fade" id="testModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="testForm">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" id="testAction" value="add_test">
                <input type="hidden" name="test_id" id="testId" value="">
                
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="testModalTitle">
                        <i class="bi bi-plus-circle me-2"></i>
                        Add Lab Test
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="testTypeId" class="form-label">
                            Test Type <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="testTypeId" name="type_id" required>
                            <option value="">-- Select Test Type --</option>
                            <?php foreach ($typeList as $type): ?>
                            <option value="<?php echo $type['type_id']; ?>">
                                <?php echo htmlspecialchars($type['type_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($typeList)): ?>
                        <div class="form-text text-danger">
                            No test types available. Please add a test type first.
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="testName" class="form-label">
                            Test Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="testName" 
                               name="test_name" 
                               required 
                               maxlength="100"
                               placeholder="e.g., Complete Blood Count (CBC)">
                    </div>
                    
                    <div class="mb-3">
                        <label for="testPrice" class="form-label">Price (RS:)</label>
                        <div class="input-group">
                            <span class="input-group-text">RS:</span>
                            <input type="number" 
                                   class="form-control" 
                                   id="testPrice" 
                                   name="test_price" 
                                   value="0.00"
                                   min="0"
                                   step="0.01"
                                   placeholder="0.00">
                        </div>
                    </div>
                    
                    <div class="mb-3" id="testStatusDiv" style="display: none;">
                        <div class="form-check form-switch">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="testIsActive" 
                                   name="is_active" 
                                   checked>
                            <label class="form-check-label" for="testIsActive">
                                Active
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle me-1"></i>
                        <span id="testSubmitText">Add Test</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- =====================================================
     MODAL: BULK PRICE UPDATE
     ===================================================== -->
<div class="modal fade" id="bulkPriceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="bulkPriceForm">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="bulk_price_update">
                
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">
                        <i class="bi bi-currency-rupee me-2"></i>
                        Bulk Price Update
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Increase or decrease all test prices by a percentage.
                    </div>
                    
                    <div class="mb-3">
                        <label for="bulkTypeId" class="form-label">Apply To</label>
                        <select class="form-select" id="bulkTypeId" name="type_id">
                            <option value="">All Test Types</option>
                            <?php foreach ($typeList as $type): ?>
                            <option value="<?php echo $type['type_id']; ?>">
                                <?php echo htmlspecialchars($type['type_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="percentage" class="form-label">
                            Percentage Change <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="number" 
                                   class="form-control" 
                                   id="percentage" 
                                   name="percentage" 
                                   required
                                   step="0.1"
                                   placeholder="e.g., 10 or -5">
                            <span class="input-group-text">%</span>
                        </div>
                        <div class="form-text">
                            Use positive number to increase (e.g., 10 for +10%)<br>
                            Use negative number to decrease (e.g., -5 for -5%)
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-check-circle me-1"></i>
                        Update Prices
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- =====================================================
     MODAL: DELETE CONFIRMATION
     ===================================================== -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="deleteForm">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" id="deleteAction" value="">
                <input type="hidden" name="type_id" id="deleteTypeId" value="">
                <input type="hidden" name="test_id" id="deleteTestId" value="">
                
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-trash me-2"></i>
                        Confirm Delete
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body text-center">
                    <i class="bi bi-exclamation-triangle text-warning display-4"></i>
                    <p class="mt-3" id="deleteMessage">
                        Are you sure you want to delete this item?
                    </p>
                    <p class="text-muted small">This action cannot be undone.</p>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i>
                        Delete
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- =====================================================
     MODAL: TOGGLE STATUS CONFIRMATION
     ===================================================== -->
<div class="modal fade" id="toggleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="toggleForm">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="toggle_test_status">
                <input type="hidden" name="test_id" id="toggleTestId" value="">
                <input type="hidden" name="status" id="toggleStatus" value="">
                
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">
                        <i class="bi bi-toggle-on me-2"></i>
                        Change Status
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body text-center">
                    <i class="bi bi-question-circle text-warning display-4"></i>
                    <p class="mt-3" id="toggleMessage">
                        Are you sure you want to change the status?
                    </p>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning" id="toggleSubmitBtn">
                        <i class="bi bi-check me-1"></i>
                        Confirm
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- =====================================================
     JAVASCRIPT
     ===================================================== -->
<script>
// =====================================================
// TEST TYPE FUNCTIONS
// =====================================================

function resetTypeModal() {
    document.getElementById('typeForm').reset();
    document.getElementById('typeAction').value = 'add_type';
    document.getElementById('typeId').value = '';
    document.getElementById('typeModalTitle').innerHTML = '<i class="bi bi-folder-plus me-2"></i>Add Test Type';
    document.getElementById('typeSubmitText').textContent = 'Add Type';
    document.getElementById('displayOrder').value = '0';
}

function editType(type) {
    document.getElementById('typeAction').value = 'edit_type';
    document.getElementById('typeId').value = type.type_id;
    document.getElementById('typeName').value = type.type_name;
    document.getElementById('displayOrder').value = type.display_order || 0;
    document.getElementById('typeModalTitle').innerHTML = '<i class="bi bi-pencil me-2"></i>Edit Test Type';
    document.getElementById('typeSubmitText').textContent = 'Update Type';
    new bootstrap.Modal(document.getElementById('typeModal')).show();
}

function deleteType(id, name, testCount) {
    if (testCount > 0) {
        alert('Cannot delete "' + name + '" because it has ' + testCount + ' test(s) assigned.\n\nPlease delete or reassign the tests first.');
        return;
    }
    
    document.getElementById('deleteAction').value = 'delete_type';
    document.getElementById('deleteTypeId').value = id;
    document.getElementById('deleteTestId').value = '';
    document.getElementById('deleteMessage').innerHTML = 
        'Are you sure you want to delete:<br><strong>' + name + '</strong>?';
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// =====================================================
// LAB TEST FUNCTIONS
// =====================================================

function resetTestModal() {
    document.getElementById('testForm').reset();
    document.getElementById('testAction').value = 'add_test';
    document.getElementById('testId').value = '';
    document.getElementById('testModalTitle').innerHTML = '<i class="bi bi-plus-circle me-2"></i>Add Lab Test';
    document.getElementById('testSubmitText').textContent = 'Add Test';
    document.getElementById('testStatusDiv').style.display = 'none';
    document.getElementById('testPrice').value = '0.00';
}

function editTest(test) {
    document.getElementById('testAction').value = 'edit_test';
    document.getElementById('testId').value = test.test_id;
    document.getElementById('testTypeId').value = test.type_id;
    document.getElementById('testName').value = test.test_name;
    document.getElementById('testPrice').value = parseFloat(test.test_price).toFixed(2);
    document.getElementById('testIsActive').checked = test.is_active == 1;
    document.getElementById('testStatusDiv').style.display = 'block';
    document.getElementById('testModalTitle').innerHTML = '<i class="bi bi-pencil me-2"></i>Edit Lab Test';
    document.getElementById('testSubmitText').textContent = 'Update Test';
    new bootstrap.Modal(document.getElementById('testModal')).show();
}

function deleteTest(id, name) {
    document.getElementById('deleteAction').value = 'delete_test';
    document.getElementById('deleteTestId').value = id;
    document.getElementById('deleteTypeId').value = '';
    document.getElementById('deleteMessage').innerHTML = 
        'Are you sure you want to delete:<br><strong>' + name + '</strong>?';
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

function toggleTestStatus(id, newStatus, name) {
    document.getElementById('toggleTestId').value = id;
    document.getElementById('toggleStatus').value = newStatus;
    
    const statusText = newStatus === 1 ? 'activate' : 'deactivate';
    document.getElementById('toggleMessage').innerHTML = 
        'Are you sure you want to <strong>' + statusText + '</strong>:<br><strong>' + name + '</strong>?';
    
    const btn = document.getElementById('toggleSubmitBtn');
    btn.className = newStatus === 1 ? 'btn btn-success' : 'btn btn-warning';
    btn.innerHTML = '<i class="bi bi-' + (newStatus === 1 ? 'play' : 'pause') + ' me-1"></i>' + 
                    (newStatus === 1 ? 'Activate' : 'Deactivate');
    
    new bootstrap.Modal(document.getElementById('toggleModal')).show();
}

// =====================================================
// INITIALIZE
// =====================================================

document.addEventListener('DOMContentLoaded', function() {
    // Update URL when tab changes
    const tabs = document.querySelectorAll('#labTabs button[data-bs-toggle="tab"]');
    tabs.forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(e) {
            const tabId = e.target.getAttribute('data-bs-target').substring(1);
            const url = new URL(window.location);
            url.searchParams.set('tab', tabId);
            url.searchParams.delete('search');
            url.searchParams.delete('type');
            window.history.replaceState({}, '', url);
        });
    });
    
    // Confirm bulk price update
    document.getElementById('bulkPriceForm').addEventListener('submit', function(e) {
        const percentage = document.getElementById('percentage').value;
        if (!confirm('Are you sure you want to update all prices by ' + percentage + '%?\n\nThis action affects multiple records.')) {
            e.preventDefault();
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>