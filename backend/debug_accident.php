<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Config\Database;
use App\Config\JWTConfig;
use App\Models\Accident;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Initialize database connection
$database = new Database();
$pdo = $database->getConnection();

// Test the Accident model directly
$accidentModel = new Accident($pdo);

// Test data that mimics what the frontend sends
$testData = [
    'car_id' => 1,
    'location_lat' => 40.7128,
    'location_lng' => -74.0060,
    'severity' => 'medium',
    'status' => 'pending',
    'description' => 'Test accident from debug script',
    'accident_time' => date('Y-m-d H:i:s')
];

echo "Testing accident creation with data:\n";
echo json_encode($testData, JSON_PRETTY_PRINT) . "\n\n";

try {
    $result = $accidentModel->create($testData);
    echo "Result: $result\n";
    
    if ($result) {
        echo "Accident created successfully with ID: $result\n";
    } else {
        echo "Failed to create accident\n";
    }
} catch (Exception $e) {
    echo "Exception caught: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
