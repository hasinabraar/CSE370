<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Config\Database;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Initialize database connection
$database = new Database();
$pdo = $database->getConnection();

echo "Checking tables:\n";
$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
foreach($tables as $table) {
    echo "- $table\n";
}

// Check if police_stations table exists
echo "\nChecking police_stations table structure:\n";
try {
    $stmt = $pdo->query('DESCRIBE police_stations');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']})\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
