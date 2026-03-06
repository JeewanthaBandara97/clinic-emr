<?php
/**
 * Debug File - Delete after fixing issues
 */

// Enable all error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>PHP Debug Test</h2>";

// Test 1: PHP is working
echo "<p>✅ PHP is working</p>";

// Test 2: Check if config loads
echo "<p>Testing config.php...</p>";
try {
    require_once __DIR__ . '/config/config.php';
    echo "<p>✅ config.php loaded successfully</p>";
    echo "<p>APP_URL: " . APP_URL . "</p>";
} catch (Exception $e) {
    echo "<p>❌ Error loading config: " . $e->getMessage() . "</p>";
}

// Test 3: Check if database class loads
echo "<p>Testing Database class...</p>";
try {
    require_once __DIR__ . '/classes/Database.php';
    echo "<p>✅ Database.php loaded successfully</p>";
} catch (Exception $e) {
    echo "<p>❌ Error loading Database: " . $e->getMessage() . "</p>";
}

// Test 4: Test database connection
echo "<p>Testing database connection...</p>";
try {
    $db = Database::getInstance();
    echo "<p>✅ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p>❌ Database connection failed: " . $e->getMessage() . "</p>";
}

// Test 5: Check if tables exist
echo "<p>Testing database tables...</p>";
try {
    $db = Database::getInstance();
    $result = $db->fetchAll("SHOW TABLES");
    echo "<p>✅ Tables found: " . count($result) . "</p>";
    echo "<ul>";
    foreach ($result as $table) {
        echo "<li>" . array_values($table)[0] . "</li>";
    }
    echo "</ul>";
} catch (Exception $e) {
    echo "<p>❌ Error checking tables: " . $e->getMessage() . "</p>";
}

// Test 6: Check users table
echo "<p>Testing users table...</p>";
try {
    $db = Database::getInstance();
    $users = $db->fetchAll("SELECT user_id, username, role_id FROM users");
    echo "<p>✅ Users found: " . count($users) . "</p>";
    echo "<pre>";
    print_r($users);
    echo "</pre>";
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong>If all tests pass, the basic setup is correct.</strong></p>";
echo "<p><a href='auth/login.php'>Go to Login Page</a></p>";