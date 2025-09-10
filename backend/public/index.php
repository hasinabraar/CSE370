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
use App\Controllers\PoliceController;
use App\Controllers\CarController;
use App\Controllers\AdminController;
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
$policeController = new PoliceController($pdo, $jwtConfig);
$carController = new CarController($pdo, $jwtConfig);
$adminController = new AdminController($pdo, $jwtConfig);

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
        case '/reportAccident':
            if ($method === 'POST') {
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

        // Hospital alert routes
        case '/hospitals/alerts':
            if ($method === 'GET') {
                $token = $authMiddleware->getTokenFromHeader();
                if (!$token) { http_response_code(401); echo json_encode(['error' => 'No token provided']); break; }
                $user = $authMiddleware->validateToken($token);
                $authMiddleware->requireAnyRole(['hospital'], $user['role']);
                $hospitalId = $_GET['hospital_id'] ?? null;
                if (!$hospitalId) { http_response_code(400); echo json_encode(['error' => 'hospital_id is required']); break; }
                echo json_encode($hospitalController->getHospitalNotifications($hospitalId, $_GET));
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;

        // Police alert routes
        case '/police/alerts':
            if ($method === 'GET') {
                $token = $authMiddleware->getTokenFromHeader();
                if (!$token) { http_response_code(401); echo json_encode(['error' => 'No token provided']); break; }
                $user = $authMiddleware->validateToken($token);
                $authMiddleware->requireAnyRole(['police'], $user['role']);
                $filters = $_GET;
                echo json_encode($policeController->getAlerts($filters));
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;

        case (preg_match('/^\/police\/alerts\/(\d+)\/read$/', $path, $matches) ? true : false):
            $alertId = $matches[1];
            if ($method === 'PUT') {
                $token = $authMiddleware->getTokenFromHeader();
                if (!$token) { http_response_code(401); echo json_encode(['error' => 'No token provided']); break; }
                $user = $authMiddleware->validateToken($token);
                $authMiddleware->requireAnyRole(['police'], $user['role']);
                $policeStationId = $_GET['police_station_id'] ?? null;
                if (!$policeStationId) { http_response_code(400); echo json_encode(['error' => 'police_station_id is required']); break; }
                echo json_encode($policeController->markAlertRead($alertId, $policeStationId, $user['user_id']));
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

        case (preg_match('/^\/hospitals\/(\d+)$/', $path, $matches) ? true : false):
            $hospitalId = $matches[1];
            if ($method === 'GET') {
                echo json_encode($hospitalController->getHospital($hospitalId));
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;

        case '/hospitals/nearby':
            if ($method === 'GET') {
                $lat = $_GET['lat'] ?? null;
                $lng = $_GET['lng'] ?? null;
                $radius = $_GET['radius'] ?? 50;
                echo json_encode($hospitalController->getNearbyHospitals($lat, $lng, $radius));
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;

        case (preg_match('/^\/hospitals\/(\d+)\/activity$/', $path, $matches) ? true : false):
            $hospitalId = $matches[1];
            if ($method === 'GET') {
                echo json_encode($hospitalController->getHospitalActivity($hospitalId));
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;

        case (preg_match('/^\/hospitals\/(\d+)\/notifications$/', $path, $matches) ? true : false):
            $hospitalId = $matches[1];
            if ($method === 'GET') {
                $token = $authMiddleware->getTokenFromHeader();
                if (!$token) { http_response_code(401); echo json_encode(['error' => 'No token provided']); break; }
                $user = $authMiddleware->validateToken($token);
                $authMiddleware->requireAnyRole(['hospital'], $user['role']);
                echo json_encode($hospitalController->getHospitalNotifications($hospitalId, $_GET));
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;

        case (preg_match('/^\/hospitals\/(\d+)\/notifications\/(\d+)\/read$/', $path, $matches) ? true : false):
            $hospitalId = $matches[1];
            $notificationId = $matches[2];
            if ($method === 'PUT') {
                $token = $authMiddleware->getTokenFromHeader();
                if (!$token) { http_response_code(401); echo json_encode(['error' => 'No token provided']); break; }
                $user = $authMiddleware->validateToken($token);
                $authMiddleware->requireAnyRole(['hospital'], $user['role']);
                echo json_encode($hospitalController->markNotificationAsRead($notificationId, $hospitalId, $user['user_id']));
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

        case (preg_match('/^\/cars\/(\d+)$/', $path, $matches) ? true : false):
            $carId = $matches[1];
            if ($method === 'GET') {
                $token = $authMiddleware->getTokenFromHeader();
                if (!$token) { http_response_code(401); echo json_encode(['error' => 'No token provided']); break; }
                $user = $authMiddleware->validateToken($token);
                echo json_encode($carController->getCar($carId, $user['user_id']));
            } elseif ($method === 'PUT') {
                $token = $authMiddleware->getTokenFromHeader();
                if (!$token) { http_response_code(401); echo json_encode(['error' => 'No token provided']); break; }
                $user = $authMiddleware->validateToken($token);
                $data = json_decode(file_get_contents('php://input'), true);
                echo json_encode($carController->updateCar($carId, $data, $user['user_id']));
            } elseif ($method === 'DELETE') {
                $token = $authMiddleware->getTokenFromHeader();
                if (!$token) { http_response_code(401); echo json_encode(['error' => 'No token provided']); break; }
                $user = $authMiddleware->validateToken($token);
                echo json_encode($carController->deleteCar($carId, $user['user_id']));
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;

        case (preg_match('/^\/cars\/(\d+)\/sensor$/', $path, $matches) ? true : false):
            $carId = $matches[1];
            if ($method === 'PUT') {
                $token = $authMiddleware->getTokenFromHeader();
                if (!$token) { http_response_code(401); echo json_encode(['error' => 'No token provided']); break; }
                $user = $authMiddleware->validateToken($token);
                $data = json_decode(file_get_contents('php://input'), true);
                echo json_encode($carController->updateSensorStatus($carId, $data, $user['user_id']));
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;

        case (preg_match('/^\/cars\/(\d+)\/accidents$/', $path, $matches) ? true : false):
            $carId = $matches[1];
            if ($method === 'GET') {
                $token = $authMiddleware->getTokenFromHeader();
                if (!$token) { http_response_code(401); echo json_encode(['error' => 'No token provided']); break; }
                $user = $authMiddleware->validateToken($token);
                echo json_encode($carController->getCarAccidents($carId, $user['user_id'], $_GET));
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;

        // Statistics alias
        case '/statistics':
            if ($method === 'GET') {
                $filters = $_GET;
                echo json_encode($accidentController->getStatistics($filters));
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;

        // Admin routes (CRUD)
        case '/admin/hospitals':
            $token = $authMiddleware->getTokenFromHeader();
            if (!$token) { http_response_code(401); echo json_encode(['error' => 'No token provided']); break; }
            $user = $authMiddleware->validateToken($token);
            $authMiddleware->requireRole('admin', $user['role']);
            if ($method === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                echo json_encode($adminController->createHospital($data));
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;

        case (preg_match('/^\/admin\/hospitals\/(\d+)$/', $path, $matches) ? true : false):
            $token = $authMiddleware->getTokenFromHeader();
            if (!$token) { http_response_code(401); echo json_encode(['error' => 'No token provided']); break; }
            $user = $authMiddleware->validateToken($token);
            $authMiddleware->requireRole('admin', $user['role']);
            $id = $matches[1];
            if ($method === 'PUT') {
                $data = json_decode(file_get_contents('php://input'), true);
                echo json_encode($adminController->updateHospital($id, $data));
            } elseif ($method === 'DELETE') {
                echo json_encode($adminController->deleteHospital($id));
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;

        case '/admin/police-stations':
            $token = $authMiddleware->getTokenFromHeader();
            if (!$token) { http_response_code(401); echo json_encode(['error' => 'No token provided']); break; }
            $user = $authMiddleware->validateToken($token);
            $authMiddleware->requireRole('admin', $user['role']);
            if ($method === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                echo json_encode($adminController->createPoliceStation($data));
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;

        case (preg_match('/^\/admin\/police-stations\/(\d+)$/', $path, $matches) ? true : false):
            $token = $authMiddleware->getTokenFromHeader();
            if (!$token) { http_response_code(401); echo json_encode(['error' => 'No token provided']); break; }
            $user = $authMiddleware->validateToken($token);
            $authMiddleware->requireRole('admin', $user['role']);
            $id = $matches[1];
            if ($method === 'PUT') {
                $data = json_decode(file_get_contents('php://input'), true);
                echo json_encode($adminController->updatePoliceStation($id, $data));
            } elseif ($method === 'DELETE') {
                echo json_encode($adminController->deletePoliceStation($id));
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
