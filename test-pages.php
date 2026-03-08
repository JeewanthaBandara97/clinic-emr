<?php
/**
 * Test individual pages
 */

// PHP Setup must come BEFORE any output
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Initialize counters
$passed = 0;
$failed = 0;
$tests = [];
$selectedPage = $_GET['page'] ?? '';

function addTest($name, $status, $message = '') {
    global $passed, $failed, $tests;
    if ($status) {
        $passed++;
        $tests[] = ['name' => $name, 'status' => 'pass', 'message' => $message];
    } else {
        $failed++;
        $tests[] = ['name' => $name, 'status' => 'fail', 'message' => $message];
    }
}

// Run tests only if a page is selected
if ($selectedPage) {
    try {
        // Load dependencies one by one
        require_once __DIR__ . '/config/config.php';
        addTest('Config Loading', true);
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        addTest('Session Initialization', true);
        
        require_once __DIR__ . '/classes/User.php';
        require_once __DIR__ . '/classes/Patient.php';
        require_once __DIR__ . '/classes/Session.php';
        addTest('Classes Loading', true);
        
        require_once __DIR__ . '/includes/functions.php';
        require_once __DIR__ . '/includes/csrf.php';
        addTest('Functions Loading', true);
        
        $patient = new Patient();
        $clinicSession = new ClinicSession();
        $userObj = new User();
        addTest('Classes Instantiation', true);
        
        if ($selectedPage === 'create-session') {
            $doctors = $userObj->getAllDoctors();
            addTest('getAllDoctors() Method', true, 'Found ' . count($doctors) . ' doctors');
        }
        
        if ($selectedPage === 'add-to-queue') {
            $activeSessions = $clinicSession->getActiveSessions();
            addTest('getActiveSessions() Method', true, 'Found ' . count($activeSessions) . ' sessions');
        }
        
        if ($selectedPage === 'todays-patients') {
            $todaysPatients = $patient->getTodaysPatients();
            addTest('getTodaysPatients() Method', true, 'Found ' . count($todaysPatients) . ' patients');
        }
        
    } catch (Exception $e) {
        addTest('Execution', false, $e->getMessage());
    }
}

// NOW output HTML after all PHP logic is complete
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Tester</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            max-width: 700px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .header p {
            margin: 8px 0 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        .content {
            padding: 30px;
        }
        .test-button {
            display: inline-block;
            padding: 12px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin: 8px 5px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        .test-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }
        .test-button.active {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b0764 100%);
        }
        .test-item {
            display: flex;
            align-items: center;
            padding: 14px;
            margin-bottom: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #dee2e6;
            transition: all 0.3s ease;
        }
        .test-item:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .test-item.pass {
            background: #d4edda;
            border-left-color: #28a745;
        }
        .test-item.fail {
            background: #f8d7da;
            border-left-color: #dc3545;
        }
        .test-icon {
            font-size: 20px;
            min-width: 30px;
            margin-right: 15px;
        }
        .test-info {
            flex-grow: 1;
        }
        .test-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 2px;
        }
        .test-message {
            font-size: 12px;
            color: #666;
            margin: 0;
        }
        .summary {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .summary-stat {
            display: inline-block;
            margin: 0 20px;
        }
        .summary-number {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .summary-label {
            font-size: 12px;
            text-transform: uppercase;
            color: #666;
            letter-spacing: 1px;
        }
        .summary-number.pass {
            color: #28a745;
        }
        .summary-number.fail {
            color: #dc3545;
        }
        .footer {
            background: #f9f9f9;
            padding: 20px 30px;
            text-align: center;
            border-top: 1px solid #dee2e6;
        }
        .footer a {
            display: inline-block;
            padding: 10px 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: transform 0.2s;
        }
        .footer a:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }
        .alert-details {
            background: #d1ecf1;
            border-left: 4px solid #0c5460;
            color: #0c5460;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .page-selector {
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📄 Page Tester</h1>
            <p>Individual Page Component Verification</p>
        </div>

        <div class="content">
<?php

if (!$selectedPage):
?>
            <div class="page-selector">
                <p style="margin-bottom: 15px; color: #666; font-size: 14px;">Select a page to test:</p>
                <a href="?page=create-session" class="test-button">Test create-session.php</a>
                <a href="?page=add-to-queue" class="test-button">Test add-to-queue.php</a>
                <a href="?page=todays-patients" class="test-button">Test todays-patients.php</a>
            </div>

            <div class="alert-details">
                <strong>ℹ️ Instructions:</strong> Click any button above to load and test the corresponding page component.
            </div>
<?php
else:
?>
            <div class="summary">
                <div class="summary-stat">
                    <div class="summary-number pass"><?php echo $passed; ?></div>
                    <div class="summary-label">Passed</div>
                </div>
                <div class="summary-stat">
                    <div class="summary-number fail"><?php echo $failed; ?></div>
                    <div class="summary-label">Failed</div>
                </div>
            </div>

            <?php if ($failed === 0 && !empty($tests)): ?>
            <div class="alert-details">
                <strong>✅ All Tests Passed!</strong> The page component is working correctly.
            </div>
            <?php endif; ?>

            <div style="margin-top: 20px;">
                <?php foreach ($tests as $test): ?>
                <div class="test-item <?php echo $test['status']; ?>">
                    <div class="test-icon">
                        <?php echo ($test['status'] === 'pass') ? '✅' : '❌'; ?>
                    </div>
                    <div class="test-info">
                        <div class="test-name"><?php echo htmlspecialchars($test['name']); ?></div>
                        <?php if (!empty($test['message'])): ?>
                        <div class="test-message"><?php echo htmlspecialchars(substr($test['message'], 0, 100)); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #dee2e6;">
                <p style="color: #666; font-size: 13px; margin-bottom: 15px;">Test another page:</p>
                <a href="?page=create-session" class="test-button">create-session.php</a>
                <a href="?page=add-to-queue" class="test-button">add-to-queue.php</a>
                <a href="?page=todays-patients" class="test-button">todays-patients.php</a>
                <a href="?" class="test-button" style="background: #6c757d;">Clear Selection</a>
            </div>
<?php
endif;
?>
        </div>

        <div class="footer">
            <a href="auth/login.php">→ Go to Login</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>