<?php
/**
 * Search Patient
 * Clinic EMR System
 */

 
$pageTitle = 'Search Patient';


require_once __DIR__ . '/../includes/auth.php';
requireDoctor();
require_once __DIR__ . '/../includes/functions.php';   // ADD THIS
require_once __DIR__ . '/../includes/csrf.php';   // ADD THIS
require_once __DIR__ . '/../classes/Patient.php';

$patient = new Patient();
$searchResults = [];
$searchTerm = '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET['search'])) {
    $searchTerm = sanitize($_GET['search']);
    if (strlen($searchTerm) >= 2) {
        $searchResults = $patient->search($searchTerm, 50);
    }
}

// Pagination for all patients
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$totalPatients = $patient->getCount();
$pagination = getPagination($totalPatients, $page);

if (empty($searchTerm)) {
    $allPatients = $patient->getAll($pagination['offset'], $pagination['per_page']);
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h1><i class="bi bi-search me-2"></i>Search Patient</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Search Patient</li>
        </ol>
    </nav>
</div>

<!-- Search Box -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-10">
                <input type="text" class="form-control form-control-lg" name="search" 
                       placeholder="Search by name, patient code, NIC, or phone number..."
                       value="<?php echo htmlspecialchars($searchTerm); ?>" autofocus>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-lg w-100">
                    <i class="bi bi-search me-2"></i>Search
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Results -->
<div class="card">
    <div class="card-header">
        <?php if ($searchTerm): ?>
            <i class="bi bi-list me-2"></i>Search Results for "<?php echo htmlspecialchars($searchTerm); ?>" 
            (<?php echo count($searchResults); ?> found)
            <a href="search-patient.php" class="btn btn-sm btn-outline-secondary float-end">Clear Search</a>
        <?php else: ?>
            <i class="bi bi-people me-2"></i>All Patients (<?php echo $totalPatients; ?>)
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <?php 
        $displayPatients = $searchTerm ? $searchResults : ($allPatients ?? []);
        ?>
        
        <?php if (empty($displayPatients)): ?>
            <div class="text-center py-5">
                <i class="bi bi-person-x display-4 text-muted"></i>
                <p class="text-muted mt-2">
                    <?php echo $searchTerm ? 'No patients found matching your search' : 'No patients registered yet'; ?>
                </p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Patient Code</th>
                            <th>Name</th>
                            <th>Age/Gender</th>
                            <th>Phone</th>
                            <th>Blood Group</th>
                            <th>Last Visit</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($displayPatients as $pt): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($pt['patient_code']); ?></code></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($pt['full_name']); ?></strong>
                                    <?php if (!empty($pt['nic_number'])): ?>
                                        <br><small class="text-muted">NIC: <?php echo htmlspecialchars($pt['nic_number']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $pt['age']; ?>y / <?php echo $pt['gender']; ?></td>
                                <td><?php echo htmlspecialchars($pt['phone']); ?></td>
                                <td><span class="badge bg-secondary"><?php echo $pt['blood_group']; ?></span></td>
                                <td><?php echo formatDate($pt['updated_at']); ?></td>
                                <td>
                                    <a href="patient-profile.php?id=<?php echo $pt['patient_id']; ?>" 
                                       class="btn btn-sm btn-primary" title="View Profile">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    <a href="patient-history.php?id=<?php echo $pt['patient_id']; ?>" 
                                       class="btn btn-sm btn-outline-secondary" title="Medical History">
                                        <i class="bi bi-clock-history"></i> History
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (!$searchTerm && $pagination['total_pages'] > 1): ?>
                <div class="card-footer">
                    <?php echo renderPagination($pagination, 'search-patient.php'); ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>