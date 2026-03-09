<?php
/**
 * Admin - Reports & Analytics
 * View comprehensive clinic statistics and reports
 */

$pageTitle = 'Reports & Analytics';
$currentPage = 'reports';

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
require_once __DIR__ . '/../classes/Reports.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$reports = new Reports();

// Get date range from request
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-01-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
?>

<div class="page-header">
    <h1><i class="bi bi-graph-up me-2"></i>Reports & Analytics</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Reports</li>
        </ol>
    </nav>
</div>

<!-- Date Range Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Start Date</label>
                <input type="date" class="form-control" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">End Date</label>
                <input type="date" class="form-control" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search me-1"></i>Generate Report
                </button>
            </div>
        </form>
    </div>
</div>

<?php
// Get all reports
$financialSummary = $reports->getFinancialSummary($startDate, $endDate);
$patientStats = $reports->getPatientVisitStats($startDate, $endDate);
$appointmentStats = $reports->getAppointmentStats($startDate, $endDate);
$doctorPerformance = $reports->getDoctorPerformanceReport($startDate, $endDate);
$medicineUsage = $reports->getMedicineUsageReport($startDate, $endDate, 10);
$labTests = $reports->getLabTestReport($startDate, $endDate);
$topDiagnoses = $reports->getTopDiagnoses(10, $startDate, $endDate);
?>

<!-- Financial Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h4 class="text-primary">Rs. <?php echo number_format($financialSummary['total_invoiced'] ?? 0, 0); ?></h4>
                <p class="text-muted mb-0">Total Revenue</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h4 class="text-success">Rs. <?php echo number_format($financialSummary['total_paid'] ?? 0, 0); ?></h4>
                <p class="text-muted mb-0">Amount Paid</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h4 class="text-danger">Rs. <?php echo number_format($financialSummary['total_outstanding'] ?? 0, 0); ?></h4>
                <p class="text-muted mb-0">Outstanding</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h4 class="text-info"><?php echo round($financialSummary['payment_percentage'] ?? 0, 1); ?>%</h4>
                <p class="text-muted mb-0">Collection Rate</p>
            </div>
        </div>
    </div>
</div>

<!-- Patient & Visit Statistics -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Patient Statistics</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td>Total Patients:</td>
                        <td><strong><?php echo $patientStats[0]['total_patients'] ?? 0; ?></strong></td>
                    </tr>
                    <tr>
                        <td>Total Visits:</td>
                        <td><strong><?php echo $patientStats[0]['total_visits'] ?? 0; ?></strong></td>
                    </tr>
                    <tr>
                        <td>Average Age:</td>
                        <td><strong><?php echo round($patientStats[0]['avg_patient_age'] ?? 0, 1); ?> years</strong></td>
                    </tr>
                    <tr>
                        <td>Male Patients:</td>
                        <td><strong><?php echo $patientStats[0]['male_patients'] ?? 0; ?></strong></td>
                    </tr>
                    <tr>
                        <td>Female Patients:</td>
                        <td><strong><?php echo $patientStats[0]['female_patients'] ?? 0; ?></strong></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Appointment Statistics</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td>Total Appointments:</td>
                        <td><strong><?php echo $appointmentStats[0]['total_appointments'] ?? 0; ?></strong></td>
                    </tr>
                    <tr>
                        <td>Completed:</td>
                        <td><span class="badge bg-success"><?php echo $appointmentStats[0]['completed'] ?? 0; ?></span></td>
                    </tr>
                    <tr>
                        <td>Scheduled:</td>
                        <td><span class="badge bg-info"><?php echo $appointmentStats[0]['scheduled'] ?? 0; ?></span></td>
                    </tr>
                    <tr>
                        <td>No Shows:</td>
                        <td><span class="badge bg-danger"><?php echo $appointmentStats[0]['no_show'] ?? 0; ?></span></td>
                    </tr>
                    <tr>
                        <td>No-Show Rate:</td>
                        <td><strong><?php echo round($appointmentStats[0]['no_show_rate'] ?? 0, 2); ?>%</strong></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Doctor Performance -->
<div class="card mb-4">
    <div class="card-header bg-light">
        <h5 class="mb-0">Doctor Performance</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Doctor</th>
                        <th>Specialization</th>
                        <th>Total Visits</th>
                        <th>Completed</th>
                        <th>Tests Recommended</th>
                        <th>Prescriptions</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($doctorPerformance as $doctor): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($doctor['doctor_name']); ?></td>
                            <td><?php echo htmlspecialchars($doctor['specialization']); ?></td>
                            <td><?php echo $doctor['total_visits'] ?? 0; ?></td>
                            <td><?php echo $doctor['completed_visits'] ?? 0; ?></td>
                            <td><?php echo $doctor['tests_recommended'] ?? 0; ?></td>
                            <td><?php echo $doctor['prescriptions_issued'] ?? 0; ?></td>
                            <td>Rs. <?php echo number_format($doctor['total_revenue'] ?? 0, 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Top Medicines Used -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Top 10 Medicines Prescribed</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Medicine</th>
                                <th>Times Prescribed</th>
                                <th>Patients</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($medicineUsage as $med): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($med['medicine_name']); ?></td>
                                    <td><strong><?php echo $med['times_prescribed']; ?></strong></td>
                                    <td><?php echo $med['unique_patients']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Lab Tests by Type</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Test Type</th>
                                <th>Requested</th>
                                <th>Completed</th>
                                <th>Pending</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($labTests as $test): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($test['type_name']); ?></td>
                                    <td><?php echo $test['total_tests']; ?></td>
                                    <td><span class="badge bg-success"><?php echo $test['completed']; ?></span></td>
                                    <td><span class="badge bg-info"><?php echo $test['processing']; ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Top Diagnoses -->
<div class="card">
    <div class="card-header bg-light">
        <h5 class="mb-0">Top 10 Diagnoses</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Diagnosis</th>
                        <th>Count</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $totalDiagnoses = array_sum(array_column($topDiagnoses, 'count'));
                    foreach ($topDiagnoses as $diagnosis):
                        $percentage = $totalDiagnoses > 0 ? round(($diagnosis['count'] / $totalDiagnoses) * 100, 2) : 0;
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($diagnosis['diagnosis']); ?></td>
                            <td><?php echo $diagnosis['count']; ?></td>
                            <td>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar" style="width: <?php echo $percentage; ?>%">
                                        <?php echo $percentage; ?>%
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
