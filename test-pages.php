<?php
/**
 * Test individual pages
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$page = $_GET['page'] ?? '';

echo "<h2>Page Tester</h2>";
echo "<p><a href='?page=create-session'>Test create-session.php</a></p>";
echo "<p><a href='?page=add-to-queue'>Test add-to-queue.php</a></p>";
echo "<p><a href='?page=todays-patients'>Test todays-patients.php</a></p>";
echo "<hr>";

if ($page) {
    echo "<h3>Testing: {$page}.php</h3>";
    
    try {
        // Load dependencies one by one
        echo "<p>Loading config...</p>";
        require_once __DIR__ . '/config/config.php';
        echo "<p>✅ Config loaded</p>";
        
        echo "<p>Starting session...</p>";
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        echo "<p>✅ Session started</p>";
        
        echo "<p>Loading classes...</p>";
        require_once __DIR__ . '/classes/User.php';
        require_once __DIR__ . '/classes/Patient.php';
        require_once __DIR__ . '/classes/Session.php';
        echo "<p>✅ Classes loaded</p>";
        
        echo "<p>Loading functions...</p>";
        require_once __DIR__ . '/includes/functions.php';
        require_once __DIR__ . '/includes/csrf.php';
        echo "<p>✅ Functions loaded</p>";
        
        echo "<p>Testing class instances...</p>";
        $patient = new Patient();
        $clinicSession = new ClinicSession();
        $userObj = new User();
        echo "<p>✅ All classes instantiated</p>";
        
        echo "<p>Testing methods...</p>";
        
        if ($page === 'create-session') {
            $doctors = $userObj->getAllDoctors();
            echo "<p>✅ getAllDoctors() works - Found: " . count($doctors) . " doctors</p>";
        }
        
        if ($page === 'add-to-queue') {
            $activeSessions = $clinicSession->getActiveSessions();
            echo "<p>✅ getActiveSessions() works - Found: " . count($activeSessions) . " sessions</p>";
        }
        
        if ($page === 'todays-patients') {
            $todaysPatients = $patient->getTodaysPatients();
            echo "<p>✅ getTodaysPatients() works - Found: " . count($todaysPatients) . " patients</p>";
        }
        
        echo "<hr>";
        echo "<p style='color:green;'><strong>✅ All tests passed! The issue might be in the HTML/view part of the file.</strong></p>";
        
    } catch (Exception $e) {
        echo "<p style='color:red;'>❌ Error: " . $e->getMessage() . "</p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
}