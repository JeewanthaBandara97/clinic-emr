<?php
/**
 * Database Migration Runner
 * Setup script to run all pending migrations
 */

// Only allow from CLI or localhost
if (php_sapi_name() !== 'cli' && $_SERVER['REMOTE_ADDR'] !== '127.0.0.1') {
    die('Access denied. Run this script from command line or localhost only.');
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

echo "=== EMR Database Migration Runner ===\n\n";

// Check which migrations have been run
$migrationsDir = __DIR__ . '/database/migrations/';
$migrationTable = 'schema_migrations';

// Create migrations table if not exists
$conn->exec("CREATE TABLE IF NOT EXISTS $migrationTable (
    version VARCHAR(50) PRIMARY KEY,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

echo "✓ Migration table ready\n";

// Get all migration files
$files = glob($migrationsDir . '*.sql');
sort($files);

if (empty($files)) {
    echo "No migration files found!\n";
    exit;
}

echo "Found " . count($files) . " migration file(s):\n";

foreach ($files as $file) {
    $filename = basename($file);
    preg_match('/^(\d+)_/', $filename, $matches);
    $version = $matches[1] ?? null;
    
    if (!$version) {
        echo "⚠ Skipping $filename (invalid format)\n";
        continue;
    }
    
    // Check if already executed
    $stmt = $conn->prepare("SELECT 1 FROM $migrationTable WHERE version = ?");
    $stmt->execute([$version]);
    
    if ($stmt->fetch()) {
        echo "⊘ Already executed: $filename\n";
        continue;
    }
    
    // Read and execute migration
    try {
        echo "→ Running migration: $filename... ";
        
        $sql = file_get_contents($file);
        
        // Split by semicolon for multiple statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $conn->exec($statement);
            }
        }
        
        // Mark as executed
        $stmt = $conn->prepare("INSERT INTO $migrationTable (version) VALUES (?)");
        $stmt->execute([$version]);
        
        echo "✓ Done\n";
        
    } catch (Exception $e) {
        echo "✗ Failed\n";
        echo "  Error: " . $e->getMessage() . "\n";
        echo "  To retry, delete the migration from $migrationTable\n";
    }
}

echo "\n=== Migration Complete ===\n";
echo "\nNext steps:\n";
echo "1. Verify all tables were created\n";
echo "2. Run: mysql clinic_emr -u root -e 'SHOW TABLES'\n";
echo "3. Test the new features in the admin panel\n";
echo "4. See FEATURES_DOCUMENTATION.md for usage guides\n";
