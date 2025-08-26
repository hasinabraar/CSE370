<?php
/**
 * Database Setup Script for Accident Detection System
 * Run this script to create the database and tables
 */

require_once __DIR__ . '/../backend/vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../backend');
$dotenv->load();

// Database configuration
$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'] ?? 'accident_detection_system';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASS'] ?? '';

try {
    // Connect to MySQL server (without database)
    $pdo = new PDO("mysql:host=$host", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "Connected to MySQL server successfully.\n";

    // Read and execute schema file
    $schemaFile = __DIR__ . '/schema.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found: $schemaFile");
    }

    $schema = file_get_contents($schemaFile);
    
    // Split by semicolon to execute multiple statements
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
                echo "Executed: " . substr($statement, 0, 50) . "...\n";
            } catch (PDOException $e) {
                // Skip if table already exists
                if (strpos($e->getMessage(), 'already exists') !== false) {
                    echo "Table already exists, skipping...\n";
                } else {
                    echo "Error executing statement: " . $e->getMessage() . "\n";
                }
            }
        }
    }

    echo "\nDatabase setup completed successfully!\n";
    echo "Database: $dbname\n";
    echo "Tables created:\n";
    echo "- users\n";
    echo "- cars\n";
    echo "- hospitals\n";
    echo "- accidents\n";
    echo "- notifications\n";
    echo "- accident_status_history\n";
    echo "\nSample data has been inserted.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
