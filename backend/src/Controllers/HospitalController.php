<?php

namespace App\Controllers;

use App\Models\Hospital;
use App\Models\User;
use App\Config\JWTConfig;

class HospitalController
{
    private $pdo;
    private $jwtConfig;
    private $hospitalModel;
    private $userModel;

    public function __construct($pdo, JWTConfig $jwtConfig)
    {
        $this->pdo = $pdo;
        $this->jwtConfig = $jwtConfig;
        $this->hospitalModel = new Hospital($pdo);
        $this->userModel = new User($pdo);
    }

    public function getHospitals($filters = [])
    {
        try {
            $hospitals = $this->hospitalModel->getAll($filters);

            return [
                'success' => true,
                'data' => $hospitals
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to get hospitals: ' . $e->getMessage()
            ];
        }
    }

    public function getHospital($id)
    {
        try {
            $hospital = $this->hospitalModel->findById($id);
            if (!$hospital) {
                return [
                    'success' => false,
                    'message' => 'Hospital not found'
                ];
            }

            return [
                'success' => true,
                'data' => $hospital
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to get hospital: ' . $e->getMessage()
            ];
        }
    }

    public function updateAmbulanceAvailability($hospitalId, $data, $userId)
    {
        try {
            // Check if user is hospital or admin
            $user = $this->userModel->findById($userId);
            if ($user['role'] !== 'hospital' && $user['role'] !== 'admin') {
                return [
                    'success' => false,
                    'message' => 'Insufficient permissions to update ambulance availability'
                ];
            }

            // Validate data
            if (!isset($data['ambulance_available'])) {
                return [
                    'success' => false,
                    'message' => 'ambulance_available field is required'
                ];
            }

            if (!is_bool($data['ambulance_available']) && !in_array($data['ambulance_available'], ['0', '1', 0, 1])) {
                return [
                    'success' => false,
                    'message' => 'ambulance_available must be a boolean value'
                ];
            }

            // Convert to boolean
            $available = (bool) $data['ambulance_available'];

            // Update ambulance availability
            $success = $this->hospitalModel->updateAmbulanceAvailability($hospitalId, $available);
            if (!$success) {
                return [
                    'success' => false,
                    'message' => 'Failed to update ambulance availability'
                ];
            }

            // Get updated hospital
            $hospital = $this->hospitalModel->findById($hospitalId);

            return [
                'success' => true,
                'message' => 'Ambulance availability updated successfully',
                'data' => $hospital
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update ambulance availability: ' . $e->getMessage()
            ];
        }
    }

    public function getNearbyHospitals($lat, $lng, $radius = 50)
    {
        try {
            // Validate coordinates
            if (!is_numeric($lat) || !is_numeric($lng)) {
                return [
                    'success' => false,
                    'message' => 'Invalid coordinates'
                ];
            }

            $hospitals = $this->hospitalModel->getNearbyHospitals($lat, $lng, $radius);

            return [
                'success' => true,
                'data' => $hospitals
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to get nearby hospitals: ' . $e->getMessage()
            ];
        }
    }

    public function getHospitalActivity($hospitalId)
    {
        try {
            $activity = $this->hospitalModel->getHospitalActivity($hospitalId);
            if (!$activity) {
                return [
                    'success' => false,
                    'message' => 'Hospital not found'
                ];
            }

            return [
                'success' => true,
                'data' => $activity
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to get hospital activity: ' . $e->getMessage()
            ];
        }
    }

    public function getHospitalNotifications($hospitalId, $filters = [])
    {
        try {
            $notifications = $this->hospitalModel->getHospitalNotifications($hospitalId, $filters);

            return [
                'success' => true,
                'data' => $notifications
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to get hospital notifications: ' . $e->getMessage()
            ];
        }
    }

    public function markNotificationAsRead($notificationId, $hospitalId, $userId)
    {
        try {
            // Check if user is hospital or admin
            $user = $this->userModel->findById($userId);
            if ($user['role'] !== 'hospital' && $user['role'] !== 'admin') {
                return [
                    'success' => false,
                    'message' => 'Insufficient permissions to mark notification as read'
                ];
            }

            $success = $this->hospitalModel->markNotificationAsRead($notificationId, $hospitalId);
            if (!$success) {
                return [
                    'success' => false,
                    'message' => 'Failed to mark notification as read'
                ];
            }

            return [
                'success' => true,
                'message' => 'Notification marked as read successfully'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to mark notification as read: ' . $e->getMessage()
            ];
        }
    }
}
