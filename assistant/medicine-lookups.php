<?php
/**
 * Medicine Lookups Management
 * Manage Categories, Generic Names, Trade Names, Units
 */

$pageTitle = 'Medicine Lookups';

require_once __DIR__ . '/../includes/auth.php';
requireAssistant();

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../classes/Medicine.php';

$medicine = new Medicine();

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
            // CATEGORY ACTIONS
            // =====================================================
            case 'add_category':
                $name = trim($_POST['category_name'] ?? '');
                $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
                $level = (int)($_POST['category_level'] ?? 1);
                $description = trim($_POST['description'] ?? '');
                
                if (empty($name)) {
                    throw new Exception('Category name is required.');
                }
                
                if ($medicine->addCategory($name, $parentId, $level, $description)) {
                    $message = 'Category added successfully.';
                    $messageType = 'success';
                } else {
                    throw new Exception('Failed to add category.');
                }
                break;
                
            case 'edit_category':
                $categoryId = (int)($_POST['category_id'] ?? 0);
                $name = trim($_POST['category_name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                
                if (empty($name)) {
                    throw new Exception('Category name is required.');
                }
                
                if ($medicine->updateCategory($categoryId, $name, $description)) {
                    $message = 'Category updated successfully.';
                    $messageType = 'success';
                } else {
                    throw new Exception('Failed to update category.');
                }
                break;
                
            case 'delete_category':
                $categoryId = (int)($_POST['category_id'] ?? 0);
                
                if ($medicine->deleteCategory($categoryId)) {
                    $message = 'Category deleted successfully.';
                    $messageType = 'success';
                } else {
                    throw new Exception('Failed to delete category.');
                }
                break;
            
            // =====================================================
            // GENERIC NAME ACTIONS
            // =====================================================
            case 'add_generic':
                $name = trim($_POST['generic_name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                
                if (empty($name)) {
                    throw new Exception('Generic name is required.');
                }
                
                if ($medicine->addGenericName($name, $description)) {
                    $message = 'Generic name added successfully.';
                    $messageType = 'success';
                } else {
                    throw new Exception('Failed to add generic name.');
                }
                break;
                
            case 'edit_generic':
                $genericId = (int)($_POST['generic_id'] ?? 0);
                $name = trim($_POST['generic_name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                
                if (empty($name)) {
                    throw new Exception('Generic name is required.');
                }
                
                if ($medicine->updateGenericName($genericId, $name, $description)) {
                    $message = 'Generic name updated successfully.';
                    $messageType = 'success';
                } else {
                    throw new Exception('Failed to update generic name.');
                }
                break;
                
            case 'delete_generic':
                $genericId = (int)($_POST['generic_id'] ?? 0);
                
                if ($medicine->deleteGenericName($genericId)) {
                    $message = 'Generic name deleted successfully.';
                    $messageType = 'success';
                } else {
                    throw new Exception('Failed to delete generic name.');
                }
                break;
            
            // =====================================================
            // TRADE NAME ACTIONS
            // =====================================================
            case 'add_trade':
                $name = trim($_POST['trade_name'] ?? '');
                $manufacturer = trim($_POST['manufacturer'] ?? '');
                
                if (empty($name)) {
                    throw new Exception('Trade name is required.');
                }
                
                if ($medicine->addTradeName($name, $manufacturer)) {
                    $message = 'Trade name added successfully.';
                    $messageType = 'success';
                } else {
                    throw new Exception('Failed to add trade name.');
                }
                break;
                
            case 'edit_trade':
                $tradeId = (int)($_POST['trade_id'] ?? 0);
                $name = trim($_POST['trade_name'] ?? '');
                $manufacturer = trim($_POST['manufacturer'] ?? '');
                
                if (empty($name)) {
                    throw new Exception('Trade name is required.');
                }
                
                if ($medicine->updateTradeName($tradeId, $name, $manufacturer)) {
                    $message = 'Trade name updated successfully.';
                    $messageType = 'success';
                } else {
                    throw new Exception('Failed to update trade name.');
                }
                break;
                
            case 'delete_trade':
                $tradeId = (int)($_POST['trade_id'] ?? 0);
                
                if ($medicine->deleteTradeName($tradeId)) {
                    $message = 'Trade name deleted successfully.';
                    $messageType = 'success';
                } else {
                    throw new Exception('Failed to delete trade name.');
                }
                break;
            
            // =====================================================
            // ISSUING UNIT ACTIONS
            // =====================================================
            case 'add_issuing_unit':
                $name = trim($_POST['unit_name'] ?? '');
                $symbol = trim($_POST['unit_symbol'] ?? '');
                $description = trim($_POST['description'] ?? '');
                
                if (empty($name)) {
                    throw new Exception('Unit name is required.');
                }
                
                if ($medicine->addIssuingUnit($name, $symbol, $description)) {
                    $message = 'Issuing unit added successfully.';
                    $messageType = 'success';
                } else {
                    throw new Exception('Failed to add issuing unit.');
                }
                break;
                
            case 'edit_issuing_unit':
                $unitId = (int)($_POST['unit_id'] ?? 0);
                $name = trim($_POST['unit_name'] ?? '');
                $symbol = trim($_POST['unit_symbol'] ?? '');
                $description = trim($_POST['description'] ?? '');
                
                if (empty($name)) {
                    throw new Exception('Unit name is required.');
                }
                
                if ($medicine->updateIssuingUnit($unitId, $name, $symbol, $description)) {
                    $message = 'Issuing unit updated successfully.';
                    $messageType = 'success';
                } else {
                    throw new Exception('Failed to update issuing unit.');
                }
                break;
                
            case 'delete_issuing_unit':
                $unitId = (int)($_POST['unit_id'] ?? 0);
                
                if ($medicine->deleteIssuingUnit($unitId)) {
                    $message = 'Issuing unit deleted successfully.';
                    $messageType = 'success';
                } else {
                    throw new Exception('Failed to delete issuing unit.');
                }
                break;
            
            // =====================================================
            // STRENGTH UNIT ACTIONS
            // =====================================================
            case 'add_strength_unit':
                $name = trim($_POST['unit_name'] ?? '');
                $symbol = trim($_POST['unit_symbol'] ?? '');
                
                if (empty($name) || empty($symbol)) {
                    throw new Exception('Unit name and symbol are required.');
                }
                
                if ($medicine->addStrengthUnit($name, $symbol)) {
                    $message = 'Strength unit added successfully.';
                    $messageType = 'success';
                } else {
                    throw new Exception('Failed to add strength unit.');
                }
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

// Categories - All levels
$mainCategories = $medicine->getMainCategories();
$allCategories = $medicine->getCategories();

// Build category tree
$categoryTree = [];
foreach ($allCategories as $cat) {
    $level = $cat['category_level'];
    $parentId = $cat['parent_id'];
    
    if ($level == 1) {
        $categoryTree[$cat['category_id']] = [
            'data' => $cat,
            'children' => []
        ];
    } elseif ($level == 2 && isset($categoryTree[$parentId])) {
        $categoryTree[$parentId]['children'][$cat['category_id']] = [
            'data' => $cat,
            'children' => []
        ];
    } elseif ($level == 3) {
        // Find parent (level 2)
        foreach ($categoryTree as $mainId => &$main) {
            if (isset($main['children'][$parentId])) {
                $main['children'][$parentId]['children'][$cat['category_id']] = [
                    'data' => $cat,
                    'children' => []
                ];
                break;
            }
        }
    }
}

// Other lookups
$genericNames = $medicine->getGenericNames();
$tradeNames = $medicine->getTradeNames();
$issuingUnits = $medicine->getIssuingUnits();
$strengthUnits = $medicine->getStrengthUnits();

// Get current tab
$activeTab = $_GET['tab'] ?? 'categories';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<!-- =====================================================
     PAGE HEADER
     ===================================================== -->
<div class="page-header d-flex justify-content-between align-items-center flex-wrap">
    <div>
        <h1>
            <i class="bi bi-gear me-2"></i>
            Medicine Lookups
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="index.php">Dashboard</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="medicines.php">Medicines</a>
                </li>
                <li class="breadcrumb-item active">Lookups</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="medicines.php" class="btn btn-outline-primary">
            <i class="bi bi-arrow-left me-1"></i>
            Back to Medicines
        </a>
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
    <div class="col-6 col-md-2 mb-2">
        <div class="card bg-primary text-white h-100">
            <div class="card-body text-center py-3">
                <h3 class="mb-0"><?php echo count($mainCategories); ?></h3>
                <small>Main Categories</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2 mb-2">
        <div class="card bg-success text-white h-100">
            <div class="card-body text-center py-3">
                <h3 class="mb-0"><?php echo count($allCategories); ?></h3>
                <small>Total Categories</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2 mb-2">
        <div class="card bg-info text-white h-100">
            <div class="card-body text-center py-3">
                <h3 class="mb-0"><?php echo count($genericNames); ?></h3>
                <small>Generic Names</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2 mb-2">
        <div class="card bg-warning text-dark h-100">
            <div class="card-body text-center py-3">
                <h3 class="mb-0"><?php echo count($tradeNames); ?></h3>
                <small>Trade Names</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2 mb-2">
        <div class="card bg-secondary text-white h-100">
            <div class="card-body text-center py-3">
                <h3 class="mb-0"><?php echo count($issuingUnits); ?></h3>
                <small>Issuing Units</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2 mb-2">
        <div class="card bg-dark text-white h-100">
            <div class="card-body text-center py-3">
                <h3 class="mb-0"><?php echo count($strengthUnits); ?></h3>
                <small>Strength Units</small>
            </div>
        </div>
    </div>
</div>

<!-- =====================================================
     TAB NAVIGATION
     ===================================================== -->
<div class="card">
    <div class="card-header p-0">
        <ul class="nav nav-tabs card-header-tabs" id="lookupTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $activeTab === 'categories' ? 'active' : ''; ?>" 
                        id="categories-tab" 
                        data-bs-toggle="tab" 
                        data-bs-target="#categories" 
                        type="button" 
                        role="tab">
                    <i class="bi bi-diagram-3 me-1"></i>
                    Categories
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $activeTab === 'generic' ? 'active' : ''; ?>" 
                        id="generic-tab" 
                        data-bs-toggle="tab" 
                        data-bs-target="#generic" 
                        type="button" 
                        role="tab">
                    <i class="bi bi-file-medical me-1"></i>
                    Generic Names
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $activeTab === 'trade' ? 'active' : ''; ?>" 
                        id="trade-tab" 
                        data-bs-toggle="tab" 
                        data-bs-target="#trade" 
                        type="button" 
                        role="tab">
                    <i class="bi bi-building me-1"></i>
                    Trade Names
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $activeTab === 'issuing' ? 'active' : ''; ?>" 
                        id="issuing-tab" 
                        data-bs-toggle="tab" 
                        data-bs-target="#issuing" 
                        type="button" 
                        role="tab">
                    <i class="bi bi-box me-1"></i>
                    Issuing Units
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $activeTab === 'strength' ? 'active' : ''; ?>" 
                        id="strength-tab" 
                        data-bs-toggle="tab" 
                        data-bs-target="#strength" 
                        type="button" 
                        role="tab">
                    <i class="bi bi-speedometer me-1"></i>
                    Strength Units
                </button>
            </li>
        </ul>
    </div>

    <div class="card-body">
        <div class="tab-content" id="lookupTabsContent">
            
            <!-- =====================================================
                 TAB 1: CATEGORIES (Hierarchical)
                 ===================================================== -->
            <div class="tab-pane fade <?php echo $activeTab === 'categories' ? 'show active' : ''; ?>" 
                 id="categories" 
                 role="tabpanel">
                
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">
                        <i class="bi bi-diagram-3 me-2"></i>
                        Drug Categories
                    </h5>
                    <button type="button" 
                            class="btn btn-primary btn-sm" 
                            data-bs-toggle="modal" 
                            data-bs-target="#categoryModal"
                            onclick="resetCategoryModal()">
                        <i class="bi bi-plus-circle me-1"></i>
                        Add Category
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="50">#</th>
                                <th>Category Name</th>
                                <th>Level</th>
                                <th>Parent Category</th>
                                <th>Description</th>
                                <th width="120" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $catIndex = 0;
                            foreach ($categoryTree as $mainId => $main): 
                                $catIndex++;
                                $mainData = $main['data'];
                            ?>
                            <!-- Main Category (Level 1) -->
                            <tr class="table-primary">
                                <td><?php echo $catIndex; ?></td>
                                <td>
                                    <strong>
                                        <i class="bi bi-folder-fill text-primary me-2"></i>
                                        <?php echo htmlspecialchars($mainData['category_name']); ?>
                                    </strong>
                                </td>
                                <td><span class="badge bg-primary">Main</span></td>
                                <td>-</td>
                                <td><?php echo htmlspecialchars($mainData['description'] ?? ''); ?></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="editCategory(<?php echo htmlspecialchars(json_encode($mainData)); ?>)"
                                            title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" 
                                            onclick="deleteCategory(<?php echo $mainData['category_id']; ?>, '<?php echo htmlspecialchars($mainData['category_name']); ?>')"
                                            title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            
                            <?php foreach ($main['children'] as $sub1Id => $sub1): 
                                $catIndex++;
                                $sub1Data = $sub1['data'];
                            ?>
                            <!-- Sub Category Level 1 -->
                            <tr>
                                <td><?php echo $catIndex; ?></td>
                                <td>
                                    <span class="ms-3">
                                        <i class="bi bi-arrow-return-right text-muted me-2"></i>
                                        <i class="bi bi-folder text-success me-1"></i>
                                        <?php echo htmlspecialchars($sub1Data['category_name']); ?>
                                    </span>
                                </td>
                                <td><span class="badge bg-success">Sub 1</span></td>
                                <td>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($mainData['category_name']); ?>
                                    </small>
                                </td>
                                <td><?php echo htmlspecialchars($sub1Data['description'] ?? ''); ?></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="editCategory(<?php echo htmlspecialchars(json_encode($sub1Data)); ?>)"
                                            title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" 
                                            onclick="deleteCategory(<?php echo $sub1Data['category_id']; ?>, '<?php echo htmlspecialchars($sub1Data['category_name']); ?>')"
                                            title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            
                            <?php foreach ($sub1['children'] as $sub2Id => $sub2): 
                                $catIndex++;
                                $sub2Data = $sub2['data'];
                            ?>
                            <!-- Sub Category Level 2 -->
                            <tr>
                                <td><?php echo $catIndex; ?></td>
                                <td>
                                    <span class="ms-5">
                                        <i class="bi bi-arrow-return-right text-muted me-2"></i>
                                        <i class="bi bi-file-earmark text-warning me-1"></i>
                                        <?php echo htmlspecialchars($sub2Data['category_name']); ?>
                                    </span>
                                </td>
                                <td><span class="badge bg-warning text-dark">Sub 2</span></td>
                                <td>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($sub1Data['category_name']); ?>
                                    </small>
                                </td>
                                <td><?php echo htmlspecialchars($sub2Data['description'] ?? ''); ?></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="editCategory(<?php echo htmlspecialchars(json_encode($sub2Data)); ?>)"
                                            title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" 
                                            onclick="deleteCategory(<?php echo $sub2Data['category_id']; ?>, '<?php echo htmlspecialchars($sub2Data['category_name']); ?>')"
                                            title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; // sub2 ?>
                            
                            <?php endforeach; // sub1 ?>
                            
                            <?php endforeach; // main ?>
                            
                            <?php if (empty($categoryTree)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox display-6 d-block mb-2"></i>
                                    No categories found. Add your first category!
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- =====================================================
                 TAB 2: GENERIC NAMES
                 ===================================================== -->
            <div class="tab-pane fade <?php echo $activeTab === 'generic' ? 'show active' : ''; ?>" 
                 id="generic" 
                 role="tabpanel">
                
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">
                        <i class="bi bi-file-medical me-2"></i>
                        Generic Names
                    </h5>
                    <button type="button" 
                            class="btn btn-primary btn-sm" 
                            data-bs-toggle="modal" 
                            data-bs-target="#genericModal"
                            onclick="resetGenericModal()">
                        <i class="bi bi-plus-circle me-1"></i>
                        Add Generic Name
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="genericTable">
                        <thead class="table-light">
                            <tr>
                                <th width="50">#</th>
                                <th>Generic Name</th>
                                <th>Description</th>
                                <th>Created</th>
                                <th width="120" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($genericNames as $index => $generic): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($generic['generic_name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($generic['description'] ?? '-'); ?></td>
                                <td>
                                    <small class="text-muted">
                                        <?php echo date('d M Y', strtotime($generic['created_at'])); ?>
                                    </small>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="editGeneric(<?php echo htmlspecialchars(json_encode($generic)); ?>)"
                                            title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" 
                                            onclick="deleteGeneric(<?php echo $generic['generic_id']; ?>, '<?php echo htmlspecialchars($generic['generic_name']); ?>')"
                                            title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($genericNames)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox display-6 d-block mb-2"></i>
                                    No generic names found.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- =====================================================
                 TAB 3: TRADE NAMES
                 ===================================================== -->
            <div class="tab-pane fade <?php echo $activeTab === 'trade' ? 'show active' : ''; ?>" 
                 id="trade" 
                 role="tabpanel">
                
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">
                        <i class="bi bi-building me-2"></i>
                        Trade Names (Brand Names)
                    </h5>
                    <button type="button" 
                            class="btn btn-primary btn-sm" 
                            data-bs-toggle="modal" 
                            data-bs-target="#tradeModal"
                            onclick="resetTradeModal()">
                        <i class="bi bi-plus-circle me-1"></i>
                        Add Trade Name
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="tradeTable">
                        <thead class="table-light">
                            <tr>
                                <th width="50">#</th>
                                <th>Trade Name</th>
                                <th>Manufacturer</th>
                                <th>Created</th>
                                <th width="120" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tradeNames as $index => $trade): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($trade['trade_name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($trade['manufacturer'] ?? '-'); ?></td>
                                <td>
                                    <small class="text-muted">
                                        <?php echo date('d M Y', strtotime($trade['created_at'])); ?>
                                    </small>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="editTrade(<?php echo htmlspecialchars(json_encode($trade)); ?>)"
                                            title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" 
                                            onclick="deleteTrade(<?php echo $trade['trade_id']; ?>, '<?php echo htmlspecialchars($trade['trade_name']); ?>')"
                                            title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($tradeNames)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox display-6 d-block mb-2"></i>
                                    No trade names found.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- =====================================================
                 TAB 4: ISSUING UNITS
                 ===================================================== -->
            <div class="tab-pane fade <?php echo $activeTab === 'issuing' ? 'show active' : ''; ?>" 
                 id="issuing" 
                 role="tabpanel">
                
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">
                        <i class="bi bi-box me-2"></i>
                        Issuing Units
                    </h5>
                    <button type="button" 
                            class="btn btn-primary btn-sm" 
                            data-bs-toggle="modal" 
                            data-bs-target="#issuingModal"
                            onclick="resetIssuingModal()">
                        <i class="bi bi-plus-circle me-1"></i>
                        Add Issuing Unit
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="50">#</th>
                                <th>Unit Name</th>
                                <th>Symbol</th>
                                <th>Description</th>
                                <th width="120" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($issuingUnits as $index => $unit): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($unit['unit_name']); ?></strong>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?php echo htmlspecialchars($unit['unit_symbol'] ?? '-'); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($unit['description'] ?? '-'); ?></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="editIssuingUnit(<?php echo htmlspecialchars(json_encode($unit)); ?>)"
                                            title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" 
                                            onclick="deleteIssuingUnit(<?php echo $unit['unit_id']; ?>, '<?php echo htmlspecialchars($unit['unit_name']); ?>')"
                                            title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($issuingUnits)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox display-6 d-block mb-2"></i>
                                    No issuing units found.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- =====================================================
                 TAB 5: STRENGTH UNITS
                 ===================================================== -->
            <div class="tab-pane fade <?php echo $activeTab === 'strength' ? 'show active' : ''; ?>" 
                 id="strength" 
                 role="tabpanel">
                
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">
                        <i class="bi bi-speedometer me-2"></i>
                        Strength Units
                    </h5>
                    <button type="button" 
                            class="btn btn-primary btn-sm" 
                            data-bs-toggle="modal" 
                            data-bs-target="#strengthModal"
                            onclick="resetStrengthModal()">
                        <i class="bi bi-plus-circle me-1"></i>
                        Add Strength Unit
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="50">#</th>
                                <th>Unit Name</th>
                                <th>Symbol</th>
                                <th width="120" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($strengthUnits as $index => $unit): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($unit['unit_name']); ?></strong>
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        <?php echo htmlspecialchars($unit['unit_symbol']); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-secondary" disabled title="Cannot edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" disabled title="Cannot delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($strengthUnits)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox display-6 d-block mb-2"></i>
                                    No strength units found.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="alert alert-info mt-3">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Note:</strong> Strength units are system-defined and cannot be edited or deleted to maintain data integrity.
                </div>
            </div>

        </div>
    </div>
</div>

<!-- =====================================================
     MODAL: ADD/EDIT CATEGORY
     ===================================================== -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="categoryForm">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" id="categoryAction" value="add_category">
                <input type="hidden" name="category_id" id="categoryId" value="">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="categoryModalTitle">
                        <i class="bi bi-plus-circle me-2"></i>
                        Add Category
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="categoryLevel" class="form-label">Category Level <span class="text-danger">*</span></label>
                        <select class="form-select" id="categoryLevel" name="category_level" required onchange="toggleParentCategory()">
                            <option value="1">Main Category</option>
                            <option value="2">Sub Category Level 1</option>
                            <option value="3">Sub Category Level 2</option>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="parentCategoryDiv" style="display: none;">
                        <label for="parentId" class="form-label">Parent Category <span class="text-danger">*</span></label>
                        <select class="form-select" id="parentId" name="parent_id">
                            <option value="">-- Select Parent --</option>
                            <?php foreach ($allCategories as $cat): ?>
                            <option value="<?php echo $cat['category_id']; ?>" 
                                    data-level="<?php echo $cat['category_level']; ?>">
                                <?php 
                                $prefix = $cat['category_level'] == 2 ? '└─ ' : '';
                                echo $prefix . htmlspecialchars($cat['category_name']); 
                                ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="categoryName" class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control" 
                               id="categoryName" 
                               name="category_name" 
                               required 
                               maxlength="100"
                               placeholder="Enter category name">
                    </div>
                    
                    <div class="mb-3">
                        <label for="categoryDescription" class="form-label">Description</label>
                        <textarea class="form-control" 
                                  id="categoryDescription" 
                                  name="description" 
                                  rows="2"
                                  placeholder="Optional description"></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>
                        <span id="categorySubmitText">Add Category</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- =====================================================
     MODAL: ADD/EDIT GENERIC NAME
     ===================================================== -->
<div class="modal fade" id="genericModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="genericForm">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" id="genericAction" value="add_generic">
                <input type="hidden" name="generic_id" id="genericId" value="">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="genericModalTitle">
                        <i class="bi bi-plus-circle me-2"></i>
                        Add Generic Name
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="genericName" class="form-label">Generic Name <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control" 
                               id="genericName" 
                               name="generic_name" 
                               required 
                               maxlength="100"
                               placeholder="e.g., Paracetamol, Amoxicillin">
                    </div>
                    
                    <div class="mb-3">
                        <label for="genericDescription" class="form-label">Description</label>
                        <textarea class="form-control" 
                                  id="genericDescription" 
                                  name="description" 
                                  rows="2"
                                  placeholder="Brief description of the generic medicine"></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>
                        <span id="genericSubmitText">Add Generic Name</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- =====================================================
     MODAL: ADD/EDIT TRADE NAME
     ===================================================== -->
<div class="modal fade" id="tradeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="tradeForm">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" id="tradeAction" value="add_trade">
                <input type="hidden" name="trade_id" id="tradeId" value="">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="tradeModalTitle">
                        <i class="bi bi-plus-circle me-2"></i>
                        Add Trade Name
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="tradeName" class="form-label">Trade Name <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control" 
                               id="tradeName" 
                               name="trade_name" 
                               required 
                               maxlength="100"
                               placeholder="e.g., Panadol, Augmentin">
                    </div>
                    
                    <div class="mb-3">
                        <label for="manufacturer" class="form-label">Manufacturer</label>
                        <input type="text" 
                               class="form-control" 
                               id="manufacturer" 
                               name="manufacturer" 
                               maxlength="100"
                               placeholder="e.g., GSK, Pfizer">
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>
                        <span id="tradeSubmitText">Add Trade Name</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- =====================================================
     MODAL: ADD/EDIT ISSUING UNIT
     ===================================================== -->
<div class="modal fade" id="issuingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="issuingForm">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" id="issuingAction" value="add_issuing_unit">
                <input type="hidden" name="unit_id" id="issuingUnitId" value="">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="issuingModalTitle">
                        <i class="bi bi-plus-circle me-2"></i>
                        Add Issuing Unit
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="issuingUnitName" class="form-label">Unit Name <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control" 
                               id="issuingUnitName" 
                               name="unit_name" 
                               required 
                               maxlength="50"
                               placeholder="e.g., Tablet, Capsule, Syrup">
                    </div>
                    
                    <div class="mb-3">
                        <label for="issuingUnitSymbol" class="form-label">Symbol/Abbreviation</label>
                        <input type="text" 
                               class="form-control" 
                               id="issuingUnitSymbol" 
                               name="unit_symbol" 
                               maxlength="20"
                               placeholder="e.g., Tab, Cap, Syr">
                    </div>
                    
                    <div class="mb-3">
                        <label for="issuingUnitDescription" class="form-label">Description</label>
                        <textarea class="form-control" 
                                  id="issuingUnitDescription" 
                                  name="description" 
                                  rows="2"
                                  placeholder="Optional description"></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>
                        <span id="issuingSubmitText">Add Unit</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- =====================================================
     MODAL: ADD STRENGTH UNIT
     ===================================================== -->
<div class="modal fade" id="strengthModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="strengthForm">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="add_strength_unit">
                
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-plus-circle me-2"></i>
                        Add Strength Unit
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="strengthUnitName" class="form-label">Unit Name <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control" 
                               id="strengthUnitName" 
                               name="unit_name" 
                               required 
                               maxlength="20"
                               placeholder="e.g., Milligram">
                    </div>
                    
                    <div class="mb-3">
                        <label for="strengthUnitSymbol" class="form-label">Symbol <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control" 
                               id="strengthUnitSymbol" 
                               name="unit_symbol" 
                               required
                               maxlength="10"
                               placeholder="e.g., mg">
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>
                        Add Unit
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
                <input type="hidden" name="category_id" id="deleteCategoryId" value="">
                <input type="hidden" name="generic_id" id="deleteGenericId" value="">
                <input type="hidden" name="trade_id" id="deleteTradeId" value="">
                <input type="hidden" name="unit_id" id="deleteUnitId" value="">
                
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-trash me-2"></i>
                        Confirm Delete
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body text-center">
                    <i class="bi bi-exclamation-triangle text-warning display-4"></i>
                    <p class="mt-3">
                        Are you sure you want to delete:<br>
                        <strong id="deleteItemName"></strong>?
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
     JAVASCRIPT
     ===================================================== -->
<script>
// =====================================================
// CATEGORY FUNCTIONS
// =====================================================

function resetCategoryModal() {
    document.getElementById('categoryForm').reset();
    document.getElementById('categoryAction').value = 'add_category';
    document.getElementById('categoryId').value = '';
    document.getElementById('categoryModalTitle').innerHTML = '<i class="bi bi-plus-circle me-2"></i>Add Category';
    document.getElementById('categorySubmitText').textContent = 'Add Category';
    document.getElementById('categoryLevel').value = '1';
    document.getElementById('categoryLevel').disabled = false;
    toggleParentCategory();
}

function editCategory(category) {
    document.getElementById('categoryAction').value = 'edit_category';
    document.getElementById('categoryId').value = category.category_id;
    document.getElementById('categoryName').value = category.category_name;
    document.getElementById('categoryDescription').value = category.description || '';
    document.getElementById('categoryLevel').value = category.category_level;
    document.getElementById('categoryLevel').disabled = true;
    document.getElementById('parentId').value = category.parent_id || '';
    document.getElementById('categoryModalTitle').innerHTML = '<i class="bi bi-pencil me-2"></i>Edit Category';
    document.getElementById('categorySubmitText').textContent = 'Update Category';
    
    toggleParentCategory();
    new bootstrap.Modal(document.getElementById('categoryModal')).show();
}

function deleteCategory(id, name) {
    document.getElementById('deleteAction').value = 'delete_category';
    document.getElementById('deleteCategoryId').value = id;
    document.getElementById('deleteItemName').textContent = name;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

function toggleParentCategory() {
    const level = parseInt(document.getElementById('categoryLevel').value);
    const parentDiv = document.getElementById('parentCategoryDiv');
    const parentSelect = document.getElementById('parentId');
    
    if (level === 1) {
        parentDiv.style.display = 'none';
        parentSelect.required = false;
        parentSelect.value = '';
    } else {
        parentDiv.style.display = 'block';
        parentSelect.required = true;
        
        // Filter options based on level
        const options = parentSelect.querySelectorAll('option');
        options.forEach(option => {
            if (option.value === '') return;
            const optionLevel = parseInt(option.dataset.level);
            
            if (level === 2) {
                // For Sub1, show only Main categories (level 1)
                option.style.display = optionLevel === 1 ? '' : 'none';
            } else if (level === 3) {
                // For Sub2, show only Sub1 categories (level 2)
                option.style.display = optionLevel === 2 ? '' : 'none';
            }
        });
    }
}

// =====================================================
// GENERIC NAME FUNCTIONS
// =====================================================

function resetGenericModal() {
    document.getElementById('genericForm').reset();
    document.getElementById('genericAction').value = 'add_generic';
    document.getElementById('genericId').value = '';
    document.getElementById('genericModalTitle').innerHTML = '<i class="bi bi-plus-circle me-2"></i>Add Generic Name';
    document.getElementById('genericSubmitText').textContent = 'Add Generic Name';
}

function editGeneric(generic) {
    document.getElementById('genericAction').value = 'edit_generic';
    document.getElementById('genericId').value = generic.generic_id;
    document.getElementById('genericName').value = generic.generic_name;
    document.getElementById('genericDescription').value = generic.description || '';
    document.getElementById('genericModalTitle').innerHTML = '<i class="bi bi-pencil me-2"></i>Edit Generic Name';
    document.getElementById('genericSubmitText').textContent = 'Update Generic Name';
    new bootstrap.Modal(document.getElementById('genericModal')).show();
}

function deleteGeneric(id, name) {
    document.getElementById('deleteAction').value = 'delete_generic';
    document.getElementById('deleteGenericId').value = id;
    document.getElementById('deleteItemName').textContent = name;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// =====================================================
// TRADE NAME FUNCTIONS
// =====================================================

function resetTradeModal() {
    document.getElementById('tradeForm').reset();
    document.getElementById('tradeAction').value = 'add_trade';
    document.getElementById('tradeId').value = '';
    document.getElementById('tradeModalTitle').innerHTML = '<i class="bi bi-plus-circle me-2"></i>Add Trade Name';
    document.getElementById('tradeSubmitText').textContent = 'Add Trade Name';
}

function editTrade(trade) {
    document.getElementById('tradeAction').value = 'edit_trade';
    document.getElementById('tradeId').value = trade.trade_id;
    document.getElementById('tradeName').value = trade.trade_name;
    document.getElementById('manufacturer').value = trade.manufacturer || '';
    document.getElementById('tradeModalTitle').innerHTML = '<i class="bi bi-pencil me-2"></i>Edit Trade Name';
    document.getElementById('tradeSubmitText').textContent = 'Update Trade Name';
    new bootstrap.Modal(document.getElementById('tradeModal')).show();
}

function deleteTrade(id, name) {
    document.getElementById('deleteAction').value = 'delete_trade';
    document.getElementById('deleteTradeId').value = id;
    document.getElementById('deleteItemName').textContent = name;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// =====================================================
// ISSUING UNIT FUNCTIONS
// =====================================================

function resetIssuingModal() {
    document.getElementById('issuingForm').reset();
    document.getElementById('issuingAction').value = 'add_issuing_unit';
    document.getElementById('issuingUnitId').value = '';
    document.getElementById('issuingModalTitle').innerHTML = '<i class="bi bi-plus-circle me-2"></i>Add Issuing Unit';
    document.getElementById('issuingSubmitText').textContent = 'Add Unit';
}

function editIssuingUnit(unit) {
    document.getElementById('issuingAction').value = 'edit_issuing_unit';
    document.getElementById('issuingUnitId').value = unit.unit_id;
    document.getElementById('issuingUnitName').value = unit.unit_name;
    document.getElementById('issuingUnitSymbol').value = unit.unit_symbol || '';
    document.getElementById('issuingUnitDescription').value = unit.description || '';
    document.getElementById('issuingModalTitle').innerHTML = '<i class="bi bi-pencil me-2"></i>Edit Issuing Unit';
    document.getElementById('issuingSubmitText').textContent = 'Update Unit';
    new bootstrap.Modal(document.getElementById('issuingModal')).show();
}

function deleteIssuingUnit(id, name) {
    document.getElementById('deleteAction').value = 'delete_issuing_unit';
    document.getElementById('deleteUnitId').value = id;
    document.getElementById('deleteItemName').textContent = name;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// =====================================================
// STRENGTH UNIT FUNCTIONS
// =====================================================

function resetStrengthModal() {
    document.getElementById('strengthForm').reset();
}

// =====================================================
// INITIALIZE
// =====================================================

document.addEventListener('DOMContentLoaded', function() {
    // Update URL when tab changes
    const tabs = document.querySelectorAll('#lookupTabs button[data-bs-toggle="tab"]');
    tabs.forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(e) {
            const tabId = e.target.getAttribute('data-bs-target').substring(1);
            const url = new URL(window.location);
            url.searchParams.set('tab', tabId);
            window.history.replaceState({}, '', url);
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>