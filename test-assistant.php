<?php
/**
 * Test Assistant Page - Debug
 */

// PHP Setup must come BEFORE any output
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Initialize counters
$passed = 0;
$failed = 0;
$tests = [];

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

// Test 1: Config
try {
    require_once __DIR__ . '/config/config.php';
    addTest('Config Loading', true);
} catch (Exception $e) {
    addTest('Config Loading', false, $e->getMessage());
}

// Test 2: Session
try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    addTest('Session Initialization', true);
} catch (Exception $e) {
    addTest('Session Initialization', false, $e->getMessage());
}

// Test 3: User class
try {
    require_once __DIR__ . '/classes/User.php';
    addTest('User Class', true);
} catch (Exception $e) {
    addTest('User Class', false, $e->getMessage());
}

// Test 4: Functions
try {
    require_once __DIR__ . '/includes/functions.php';
    addTest('Functions Loaded', true);
} catch (Exception $e) {
    addTest('Functions Loaded', false, $e->getMessage());
}

// Test 5: CSRF
try {
    require_once __DIR__ . '/includes/csrf.php';
    addTest('CSRF Protection', true);
} catch (Exception $e) {
    addTest('CSRF Protection', false, $e->getMessage());
}

// Test 6: Patient class
try {
    require_once __DIR__ . '/classes/Patient.php';
    addTest('Patient Class', true);
} catch (Exception $e) {
    addTest('Patient Class', false, $e->getMessage());
}

// Test 7: Session class
try {
    require_once __DIR__ . '/classes/Session.php';
    addTest('Session Class', true);
} catch (Exception $e) {
    addTest('Session Class', false, $e->getMessage());
}

// Test 8: Patient class instantiation
try {
    $patient = new Patient();
    addTest('Patient Instantiation', true);
} catch (Exception $e) {
    addTest('Patient Instantiation', false, $e->getMessage());
}

// Test 9: ClinicSession class
try {
    $session = new ClinicSession();
    addTest('ClinicSession Instantiation', true);
} catch (Exception $e) {
    addTest('ClinicSession Instantiation', false, $e->getMessage());
}

// Test 10: getTodayStats
try {
    $stats = $session->getTodayStats();
    addTest('Statistics Retrieval', true, json_encode($stats));
} catch (Exception $e) {
    addTest('Statistics Retrieval', false, $e->getMessage());
}

// NOW output HTML after all PHP logic is complete
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assistant System Test</title>
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
        .alert-box {
            padding: 12px;
            border-radius: 6px;
            margin: 15px 0;
            font-size: 13px;
        }
        .alert-details {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            color: #856404;
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚙️ System Test Dashboard</h1>
            <p>Assistant Page Component Verification</p>
        </div>

        <div class="content">
<?php

// Display Summary
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

            <?php if ($failed > 0): ?>
            <div class="alert-details">
                <strong>⚠️ Notice:</strong> Some tests have failed. Review the details below.
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
        </div>

        <div class="footer">
            <p style="margin: 0 0 15px 0; color: #666; font-size: 13px;">
                All tests <?php echo ($failed === 0) ? '✅ Completed Successfully' : '⚠️ Completed with Issues'; ?>
            </p>
            <a href="auth/login.php">→ Go to Login</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>