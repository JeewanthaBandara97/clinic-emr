<?php
/**
 * Test Assistant Page - Debug
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Testing Assistant Page Components</h2>";

// Test 1: Config
echo "<p>1. Loading config...</p>";
require_once __DIR__ . '/config/config.php';
echo "<p>✅ Config loaded</p>";

// Test 2: Session
echo "<p>2. Starting session...</p>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "<p>✅ Session started</p>";

// Test 3: User class
echo "<p>3. Loading User class...</p>";
require_once __DIR__ . '/classes/User.php';
echo "<p>✅ User class loaded</p>";

// Test 4: Functions
echo "<p>4. Loading functions...</p>";
require_once __DIR__ . '/includes/functions.php';
echo "<p>✅ Functions loaded</p>";

// Test 5: CSRF
echo "<p>5. Loading CSRF...</p>";
require_once __DIR__ . '/includes/csrf.php';
echo "<p>✅ CSRF loaded</p>";

// Test 6: Patient class
echo "<p>6. Loading Patient class...</p>";
require_once __DIR__ . '/classes/Patient.php';
echo "<p>✅ Patient class loaded</p>";

// Test 7: Session class
echo "<p>7. Loading Session class...</p>";
require_once __DIR__ . '/classes/Session.php';
echo "<p>✅ Session class loaded</p>";

// Test 8: Test Patient class
echo "<p>8. Testing Patient class...</p>";
try {
    $patient = new Patient();
    echo "<p>✅ Patient class works</p>";
} catch (Exception $e) {
    echo "<p>❌ Patient error: " . $e->getMessage() . "</p>";
}

// Test 9: Test ClinicSession class
echo "<p>9. Testing ClinicSession class...</p>";
try {
    $session = new ClinicSession();
    echo "<p>✅ ClinicSession class works</p>";
} catch (Exception $e) {
    echo "<p>❌ ClinicSession error: " . $e->getMessage() . "</p>";
}

// Test 10: Test getTodayStats
echo "<p>10. Testing getTodayStats...</p>";
try {
    $stats = $session->getTodayStats();
    echo "<p>✅ Stats retrieved</p>";
    echo "<pre>";
    print_r($stats);
    echo "</pre>";
} catch (Exception $e) {
    echo "<p>❌ Stats error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong>All tests completed!</strong></p>";
echo "<p><a href='auth/login.php'>Go to Login</a></p>";