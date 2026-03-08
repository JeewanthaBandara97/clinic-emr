<?php
/**
 * Debug File - Delete after fixing issues
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

// Test 1: PHP is working
addTest('PHP Runtime', true, 'PHP engine is operational');

// Test 2: Check if config loads
try {
    require_once __DIR__ . '/config/config.php';
    addTest('Config Loading', true, 'APP_URL: ' . APP_URL);
} catch (Exception $e) {
    addTest('Config Loading', false, $e->getMessage());
}

// Test 3: Check if database class loads
try {
    require_once __DIR__ . '/classes/Database.php';
    addTest('Database Class', true, 'Database.php loaded');
} catch (Exception $e) {
    addTest('Database Class', false, $e->getMessage());
}

// Test 4: Test database connection
try {
    $db = Database::getInstance();
    addTest('Database Connection', true, 'Connected successfully');
} catch (Exception $e) {
    addTest('Database Connection', false, $e->getMessage());
}

// Test 5: Check if tables exist
try {
    $db = Database::getInstance();
    $result = $db->fetchAll("SHOW TABLES");
    addTest('Database Tables', true, 'Found ' . count($result) . ' tables');
} catch (Exception $e) {
    addTest('Database Tables', false, $e->getMessage());
}

// Test 6: Check users table
try {
    $db = Database::getInstance();
    $users = $db->fetchAll("SELECT user_id, username, role_id FROM users");
    addTest('Users Table', true, 'Found ' . count($users) . ' users');
} catch (Exception $e) {
    addTest('Users Table', false, $e->getMessage());
}

// NOW output HTML after all PHP logic is complete
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Debug Test</title>
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
            margin: 0 5px;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🐛 PHP Debug Dashboard</h1>
            <p>System Configuration & Database Verification</p>
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

            <?php if ($failed === 0): ?>
            <div class="alert-details">
                <strong>✅ All Tests Passed!</strong> The basic setup is correct.
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
                <?php echo ($failed === 0) ? '✅ Setup Complete' : '⚠️ Issues Detected'; ?>
            </p>
            <a href="auth/login.php">→ Go to Login</a>
            <a href="test-assistant.php">→ Assistant Tests</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>