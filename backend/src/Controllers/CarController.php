<?php

namespace App\Controllers;

use App\Models\Car;
use App\Models\User;
use App\Config\JWTConfig;

class CarController
{
    private $pdo;
    private $jwtConfig;
    private $carModel;
    private $userModel;

    public function __construct($pdo, JWTConfig $jwtConfig)
    {
        $this->pdo = $pdo;
        $this->jwtConfig = $jwtConfig;
        $this->carModel = new Car($pdo);
        $this->userModel = new User($pdo);
    }

    public function registerCar($data, $userId)
    {
        try {
            // Validate required fields
            $requiredFields = ['plate_number', 'model', 'year'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    return [
                        'success' => false,
                        'message' => "Field '$field' is required"
                    ];
                }
            }

            // Validate plate number format (basic validation)
            if (strlen($data['plate_number']) < 3) {
                return [
                    'success' => false,
                    'message' => 'Plate number must be at least 3 characters long'
                ];
            }

            // Validate year
            $currentYear = date('Y');
            if (!is_numeric($data['year']) || $data['year'] < 1900 || $data['year'] > $currentYear + 1) {
                return [
                    'success' => false,
                    'message' => 'Invalid year. Must be between 1900 and ' . ($currentYear + 1)
                ];
            }

            // Check if plate number already exists
            if ($this->carModel->checkPlateNumberExists($data['plate_number'])) {
                return [
                    'success' => false,
                    'message' => 'Plate number already registered'
                ];
            }

            // Set default values
            $carData = [
                'owner_id' => $userId,
                'plate_number' => strtoupper($data['plate_number']),
                'sensor_status' => $data['sensor_status'] ?? 'active',
                'model' => $data['model'],
                'year' => $data['year']
            ];

            // Validate sensor status
            $validStatuses = ['active', 'inactive', 'maintenance'];
            if (!in_array($carData['sensor_status'], $validStatuses)) {
                return [
                    'success' => false,
                    'message' => 'Invalid sensor status. Must be one of: ' . implode(', ', $validStatuses)
                ];
            }

            // Create car
            $carId = $this->carModel->create($carData);
            if (!$carId) {
                return [
                    'success' => false,
                    'message' => 'Failed to register car'
                ];
            }

            // Get created car
            $car = $this->carModel->findById($carId);

            return [
                'success' => true,
                'message' => 'Car registered successfully',
                'data' => $car
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to register car: ' . $e->getMessage()
            ];
        }
    }

    public function getUserCars($userId)
    {
        try {
            $cars = $this->carModel->getUserCars($userId);

            return [
                'success' => true,
                'data' => $cars
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to get user cars: ' . $e->getMessage()
            ];
        }
    }

    public function getCar($id, $userId)
    {
        try {
            $car = $this->carModel->findById($id);
            if (!$car) {
                return [
                    'success' => false,
                    'message' => 'Car not found'
                ];
            }

            // Check if car belongs to user (unless admin)
            $user = $this->userModel->findById($userId);
            if ($user['role'] !== 'admin' && $car['owner_id'] != $userId) {
                return [
                    'success' => false,
                    'message' => 'Access denied'
                ];
            }

            return [
                'success' => true,
                'data' => $car
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to get car: ' . $e->getMessage()
            ];
        }
    }

    public function updateCar($id, $data, $userId)
    {
        try {
            // Get current car
            $car = $this->carModel->findById($id);
            if (!$car) {
                return [
                    'success' => false,
                    'message' => 'Car not found'
                ];
            }

            // Check if car belongs to user (unless admin)
            $user = $this->userModel->findById($userId);
            if ($user['role'] !== 'admin' && $car['owner_id'] != $userId) {
                return [
                    'success' => false,
                    'message' => 'Access denied'
                ];
            }

            // Validate plate number if being updated
            if (isset($data['plate_number'])) {
                if (strlen($data['plate_number']) < 3) {
                    return [
                        'success' => false,
                        'message' => 'Plate number must be at least 3 characters long'
                    ];
                }

                // Check if plate number already exists (excluding current car)
                if ($this->carModel->checkPlateNumberExists($data['plate_number'], $id)) {
                    return [
                        'success' => false,
                        'message' => 'Plate number already registered'
                    ];
                }
            }

            // Validate year if being updated
            if (isset($data['year'])) {
                $currentYear = date('Y');
                if (!is_numeric($data['year']) || $data['year'] < 1900 || $data['year'] > $currentYear + 1) {
                    return [
                        'success' => false,
                        'message' => 'Invalid year. Must be between 1900 and ' . ($currentYear + 1)
                    ];
                }
            }

            // Validate sensor status if being updated
            if (isset($data['sensor_status'])) {
                $validStatuses = ['active', 'inactive', 'maintenance'];
                if (!in_array($data['sensor_status'], $validStatuses)) {
                    return [
                        'success' => false,
                        'message' => 'Invalid sensor status. Must be one of: ' . implode(', ', $validStatuses)
                    ];
                }
            }

            // Update car
            $updateData = [
                'plate_number' => $data['plate_number'] ?? $car['plate_number'],
                'model' => $data['model'] ?? $car['model'],
                'year' => $data['year'] ?? $car['year'],
                'sensor_status' => $data['sensor_status'] ?? $car['sensor_status']
            ];

            $success = $this->carModel->update($id, $updateData, $userId);
            if (!$success) {
                return [
                    'success' => false,
                    'message' => 'Failed to update car'
                ];
            }

            // Get updated car
            $updatedCar = $this->carModel->findById($id);

            return [
                'success' => true,
                'message' => 'Car updated successfully',
                'data' => $updatedCar
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update car: ' . $e->getMessage()
            ];
        }
    }

    public function deleteCar($id, $userId)
    {
        try {
            // Get current car
            $car = $this->carModel->findById($id);
            if (!$car) {
                return [
                    'success' => false,
                    'message' => 'Car not found'
                ];
            }

            // Check if car belongs to user (unless admin)
            $user = $this->userModel->findById($userId);
            if ($user['role'] !== 'admin' && $car['owner_id'] != $userId) {
                return [
                    'success' => false,
                    'message' => 'Access denied'
                ];
            }

            $success = $this->carModel->delete($id, $userId);
            if (!$success) {
                return [
                    'success' => false,
                    'message' => 'Failed to delete car'
                ];
            }

            return [
                'success' => true,
                'message' => 'Car deleted successfully'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to delete car: ' . $e->getMessage()
            ];
        }
    }

    public function updateSensorStatus($id, $data, $userId)
    {
        try {
            // Get current car
            $car = $this->carModel->findById($id);
            if (!$car) {
                return [
                    'success' => false,
                    'message' => 'Car not found'
                ];
            }

            // Check if car belongs to user (unless admin)
            $user = $this->userModel->findById($userId);
            if ($user['role'] !== 'admin' && $car['owner_id'] != $userId) {
                return [
                    'success' => false,
                    'message' => 'Access denied'
                ];
            }

            // Validate sensor status
            if (!isset($data['sensor_status'])) {
                return [
                    'success' => false,
                    'message' => 'sensor_status field is required'
                ];
            }

            $validStatuses = ['active', 'inactive', 'maintenance'];
            if (!in_array($data['sensor_status'], $validStatuses)) {
                return [
                    'success' => false,
                    'message' => 'Invalid sensor status. Must be one of: ' . implode(', ', $validStatuses)
                ];
            }

            $success = $this->carModel->updateSensorStatus($id, $data['sensor_status'], $userId);
            if (!$success) {
                return [
                    'success' => false,
                    'message' => 'Failed to update sensor status'
                ];
            }

            // Get updated car
            $updatedCar = $this->carModel->findById($id);

            return [
                'success' => true,
                'message' => 'Sensor status updated successfully',
                'data' => $updatedCar
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update sensor status: ' . $e->getMessage()
            ];
        }
    }

    public function getCarAccidents($carId, $userId, $filters = [])
    {
        try {
            // Get current car
            $car = $this->carModel->findById($carId);
            if (!$car) {
                return [
                    'success' => false,
                    'message' => 'Car not found'
                ];
            }

            // Check if car belongs to user (unless admin)
            $user = $this->userModel->findById($userId);
            if ($user['role'] !== 'admin' && $car['owner_id'] != $userId) {
                return [
                    'success' => false,
                    'message' => 'Access denied'
                ];
            }

            $accidents = $this->carModel->getCarAccidents($carId, $filters);

            return [
                'success' => true,
                'data' => $accidents
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to get car accidents: ' . $e->getMessage()
            ];
        }
    }
}
