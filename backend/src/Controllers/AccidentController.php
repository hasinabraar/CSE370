<?php

namespace App\Controllers;

use App\Models\Accident;
use App\Models\Car;
use App\Models\User;
use App\Config\JWTConfig;

class AccidentController
{
    private $pdo;
    private $jwtConfig;
    private $accidentModel;
    private $carModel;
    private $userModel;

    public function __construct($pdo, JWTConfig $jwtConfig)
    {
        $this->pdo = $pdo;
        $this->jwtConfig = $jwtConfig;
        $this->accidentModel = new Accident($pdo);
        $this->carModel = new Car($pdo);
        $this->userModel = new User($pdo);
    }

    public function createAccident($data, $userId)
    {
        try {
            // Validate required fields
            $requiredFields = ['car_id', 'location_lat', 'location_lng', 'severity'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    return [
                        'success' => false,
                        'message' => "Field '$field' is required"
                    ];
                }
            }

            // Validate coordinates
            if (!is_numeric($data['location_lat']) || !is_numeric($data['location_lng'])) {
                return [
                    'success' => false,
                    'message' => 'Invalid coordinates'
                ];
            }

            // Validate severity
            $validSeverities = ['low', 'medium', 'high', 'critical'];
            if (!in_array($data['severity'], $validSeverities)) {
                return [
                    'success' => false,
                    'message' => 'Invalid severity. Must be one of: ' . implode(', ', $validSeverities)
                ];
            }

            // Check if car belongs to user
            $car = $this->carModel->findById($data['car_id']);
            if (!$car || $car['owner_id'] != $userId) {
                return [
                    'success' => false,
                    'message' => 'Car not found or access denied'
                ];
            }

            // Set default values
            $accidentData = [
                'car_id' => $data['car_id'],
                'location_lat' => $data['location_lat'],
                'location_lng' => $data['location_lng'],
                'accident_time' => $data['accident_time'] ?? date('Y-m-d H:i:s'),
                'severity' => $data['severity'],
                'status' => 'pending',
                'description' => $data['description'] ?? ''
            ];

            // Create accident
            $accidentId = $this->accidentModel->create($accidentData);
            if (!$accidentId) {
                return [
                    'success' => false,
                    'message' => 'Failed to create accident report'
                ];
            }

            // Get created accident
            $accident = $this->accidentModel->findById($accidentId);

            return [
                'success' => true,
                'message' => 'Accident reported successfully. Nearest hospital has been notified.',
                'data' => $accident
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to create accident: ' . $e->getMessage()
            ];
        }
    }

    public function getAccidents($filters = [])
    {
        try {
            $accidents = $this->accidentModel->getAll($filters);

            return [
                'success' => true,
                'data' => $accidents
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to get accidents: ' . $e->getMessage()
            ];
        }
    }

    public function getAccident($id)
    {
        try {
            $accident = $this->accidentModel->findById($id);
            if (!$accident) {
                return [
                    'success' => false,
                    'message' => 'Accident not found'
                ];
            }

            return [
                'success' => true,
                'data' => $accident
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to get accident: ' . $e->getMessage()
            ];
        }
    }

    public function updateAccident($id, $data, $userId)
    {
        try {
            // Get current accident
            $accident = $this->accidentModel->findById($id);
            if (!$accident) {
                return [
                    'success' => false,
                    'message' => 'Accident not found'
                ];
            }

            // Check permissions (only hospital users or admins can update)
            $user = $this->userModel->findById($userId);
            if ($user['role'] !== 'hospital' && $user['role'] !== 'admin') {
                return [
                    'success' => false,
                    'message' => 'Insufficient permissions to update accident'
                ];
            }

            // Validate status
            if (isset($data['status'])) {
                $validStatuses = ['pending', 'in_progress', 'resolved', 'cancelled'];
                if (!in_array($data['status'], $validStatuses)) {
                    return [
                        'success' => false,
                        'message' => 'Invalid status. Must be one of: ' . implode(', ', $validStatuses)
                    ];
                }
            }

            // Update accident
            $updateData = [
                'status' => $data['status'] ?? $accident['status'],
                'description' => $data['description'] ?? $accident['description'],
                'change_reason' => $data['change_reason'] ?? ''
            ];

            $success = $this->accidentModel->update($id, $updateData, $userId);
            if (!$success) {
                return [
                    'success' => false,
                    'message' => 'Failed to update accident'
                ];
            }

            // Get updated accident
            $updatedAccident = $this->accidentModel->findById($id);

            return [
                'success' => true,
                'message' => 'Accident updated successfully',
                'data' => $updatedAccident
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update accident: ' . $e->getMessage()
            ];
        }
    }

    public function getStatistics($filters = [])
    {
        try {
            $stats = $this->accidentModel->getStatistics($filters);

            // Get daily statistics for the last 30 days if no date range specified
            if (empty($filters['start_date']) && empty($filters['end_date'])) {
                $endDate = date('Y-m-d');
                $startDate = date('Y-m-d', strtotime('-30 days'));
                $dailyStats = $this->accidentModel->getDailyStats($startDate, $endDate);
                $stats['daily_stats'] = $dailyStats;
            } elseif (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                $dailyStats = $this->accidentModel->getDailyStats($filters['start_date'], $filters['end_date']);
                $stats['daily_stats'] = $dailyStats;
            }

            return [
                'success' => true,
                'data' => $stats
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to get statistics: ' . $e->getMessage()
            ];
        }
    }

    public function getUserAccidents($userId, $filters = [])
    {
        try {
            // Get user's cars
            $cars = $this->carModel->getUserCars($userId);
            $carIds = array_column($cars, 'id');

            if (empty($carIds)) {
                return [
                    'success' => true,
                    'data' => []
                ];
            }

            // Add car filter
            $filters['car_ids'] = $carIds;
            
            // Modify the query to filter by user's cars
            $accidents = $this->accidentModel->getAll($filters);

            return [
                'success' => true,
                'data' => $accidents
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to get user accidents: ' . $e->getMessage()
            ];
        }
    }

    public function getHospitalAccidents($hospitalId, $filters = [])
    {
        try {
            // Add hospital filter
            $filters['hospital_id'] = $hospitalId;
            
            $accidents = $this->accidentModel->getAll($filters);

            return [
                'success' => true,
                'data' => $accidents
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to get hospital accidents: ' . $e->getMessage()
            ];
        }
    }
}
