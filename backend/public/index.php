<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use App\Config\JWTConfig;
use App\Controllers\AuthController;
use App\Controllers\AccidentController;
use App\Controllers\HospitalController;
use App\Controllers\CarController;
use App\Middleware\AuthMiddleware;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Initialize database connection
$database = new Database();
$pdo = $database->getConnection();

// Initialize JWT configuration
$jwtConfig = new JWTConfig();

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/api', '', $path);

// Initialize controllers
$authController = new AuthController($pdo, $jwtConfig);
$accidentController = new AccidentController($pdo, $jwtConfig);
$hospitalController = new HospitalController($pdo, $jwtConfig);
$carController = new CarController($pdo, $jwtConfig);

// Initialize middleware
$authMiddleware = new AuthMiddleware($jwtConfig);

try {
    // Route handling
    switch ($path) {
        // Authentication routes
        case '/auth/register':
            if ($method === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                echo json_encode($authController->register($data));
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;

        case '/auth/login':
            if ($method === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                echo json_encode($authController->login($data));
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;

        case '/auth/profile':
            if ($method === 'GET') {
                $token = $authMiddleware->getTokenFromHeader();
                if (!$token) {
                    http_response_code(401);
                    echo json_encode(['error' => 'No token provided']);
                    break;
                }
                $user = $authMiddleware->validateToken($token);
                echo json_encode($authController->getProfile($user['user_id']));
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;

        // Accident routes
        case '/accidents':
            if ($method === 'GET') {
                $filters = $_GET;
                echo json_encode($accidentController->getAccidents($filters));
            } elseif ($method === 'POST') {
                $token = $authMiddleware->getTokenFromHeader();
                if (!$token) {
                    http_response_code(401);
                    echo json_encode(['error' => 'No token provided']);
                    break;
                }
                $user = $authMiddleware->validateToken($token);
                $data = json_decode(file_get_contents('php://input'), true);
                echo json_encode($accidentController->createAccident($data, $user['user_id']));
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;

        case (preg_match('/^\/accidents\/(\d+)$/', $path, $matches) ? true : false):
            $accidentId = $matches[1];
            if ($method === 'GET') {
                echo json_encode($accidentController->getAccident($accidentId));
            } elseif ($method === 'PUT') {
                $token = $authMiddleware->getTokenFromHeader();
                if (!$token) {
                    http_response_code(401);
                    echo json_encode(['error' => 'No token provided']);
                    break;
                }
                $user = $authMiddleware->validateToken($token);
                $data = json_decode(file_get_contents('php://input'), true);
                echo json_encode($accidentController->updateAccident($accidentId, $data, $user['user_id']));
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;

        case '/accidents/statistics':
            if ($method === 'GET') {
                $filters = $_GET;
                echo json_encode($accidentController->getStatistics($filters));
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;

        // Hospital routes
        case '/hospitals':
            if ($method === 'GET') {
                echo json_encode($hospitalController->getHospitals());
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;

        case (preg_match('/^\/hospitals\/(\d+)\/ambulance$/', $path, $matches) ? true : false):
            $hospitalId = $matches[1];
            if ($method === 'PUT') {
                $token = $authMiddleware->getTokenFromHeader();
                if (!$token) {
                    http_response_code(401);
                    echo json_encode(['error' => 'No token provided']);
                    break;
                }
                $user = $authMiddleware->validateToken($token);
                $data = json_decode(file_get_contents('php://input'), true);
                echo json_encode($hospitalController->updateAmbulanceAvailability($hospitalId, $data, $user['user_id']));
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;

        // Car routes
        case '/cars':
            if ($method === 'GET') {
                $token = $authMiddleware->getTokenFromHeader();
                if (!$token) {
                    http_response_code(401);
                    echo json_encode(['error' => 'No token provided']);
                    break;
                }
                $user = $authMiddleware->validateToken($token);
                echo json_encode($carController->getUserCars($user['user_id']));
            } elseif ($method === 'POST') {
                $token = $authMiddleware->getTokenFromHeader();
                if (!$token) {
                    http_response_code(401);
                    echo json_encode(['error' => 'No token provided']);
                    break;
                }
                $user = $authMiddleware->validateToken($token);
                $data = json_decode(file_get_contents('php://input'), true);
                echo json_encode($carController->registerCar($data, $user['user_id']));
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;

        default:
            http_response_code(404);
            echo json_encode(['error' => 'Route not found']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}
