<?php

namespace App\Controllers;

use App\Models\PoliceStation;
use App\Models\PoliceAlert;
use App\Models\User;
use App\Config\JWTConfig;

class PoliceController
{
	private $pdo;
	private $jwtConfig;
	private $policeStationModel;
	private $policeAlertModel;
	private $userModel;

	public function __construct($pdo, JWTConfig $jwtConfig)
	{
		$this->pdo = $pdo;
		$this->jwtConfig = $jwtConfig;
		$this->policeStationModel = new PoliceStation($pdo);
		$this->policeAlertModel = new PoliceAlert($pdo);
		$this->userModel = new User($pdo);
	}

	public function getAlerts($filters = [])
	{
		try {
			$alerts = $this->policeAlertModel->getAll($filters);
			return [ 'success' => true, 'data' => $alerts ];
		} catch (\Exception $e) {
			return [ 'success' => false, 'message' => 'Failed to get police alerts: ' . $e->getMessage() ];
		}
	}

	public function markAlertRead($alertId, $policeStationId, $userId)
	{
		try {
			$user = $this->userModel->findById($userId);
			if ($user['role'] !== 'police' && $user['role'] !== 'admin') {
				return [ 'success' => false, 'message' => 'Insufficient permissions' ];
			}
			$ok = $this->policeAlertModel->markAsRead($alertId, $policeStationId);
			if (!$ok) {
				return [ 'success' => false, 'message' => 'Failed to mark alert as read' ];
			}
			return [ 'success' => true, 'message' => 'Alert marked as read' ];
		} catch (\Exception $e) {
			return [ 'success' => false, 'message' => 'Failed to update alert: ' . $e->getMessage() ];
		}
	}

	public function getStations($filters = [])
	{
		try {
			$stations = $this->policeStationModel->getAll($filters);
			return [ 'success' => true, 'data' => $stations ];
		} catch (\Exception $e) {
			return [ 'success' => false, 'message' => 'Failed to get stations: ' . $e->getMessage() ];
		}
	}

	public function getStation($id)
	{
		try {
			$station = $this->policeStationModel->findById($id);
			if (!$station) {
				return [ 'success' => false, 'message' => 'Police station not found' ];
			}
			return [ 'success' => true, 'data' => $station ];
		} catch (\Exception $e) {
			return [ 'success' => false, 'message' => 'Failed to get station: ' . $e->getMessage() ];
		}
	}

	public function createStation($data, $userId)
	{
		try {
			// Validate required fields
			$required = ['name', 'jurisdiction', 'latitude', 'longitude', 'address', 'phone'];
			foreach ($required as $field) {
				if (empty($data[$field])) {
					return [ 'success' => false, 'message' => "Field '$field' is required" ];
				}
			}

			// Validate coordinates
			if (!is_numeric($data['latitude']) || !is_numeric($data['longitude'])) {
				return [ 'success' => false, 'message' => 'Invalid coordinates' ];
			}

			$stationId = $this->policeStationModel->create($data);
			if (!$stationId) {
				return [ 'success' => false, 'message' => 'Failed to create police station' ];
			}

			$station = $this->policeStationModel->findById($stationId);
			return [ 'success' => true, 'data' => $station, 'message' => 'Police station created successfully' ];
		} catch (\Exception $e) {
			return [ 'success' => false, 'message' => 'Failed to create station: ' . $e->getMessage() ];
		}
	}

	public function updateStation($id, $data, $userId)
	{
		try {
			// Check if station exists
			$station = $this->policeStationModel->findById($id);
			if (!$station) {
				return [ 'success' => false, 'message' => 'Police station not found' ];
			}

			// Validate required fields
			$required = ['name', 'jurisdiction', 'latitude', 'longitude', 'address', 'phone'];
			foreach ($required as $field) {
				if (empty($data[$field])) {
					return [ 'success' => false, 'message' => "Field '$field' is required" ];
				}
			}

			// Validate coordinates
			if (!is_numeric($data['latitude']) || !is_numeric($data['longitude'])) {
				return [ 'success' => false, 'message' => 'Invalid coordinates' ];
			}

			$success = $this->policeStationModel->update($id, $data);
			if (!$success) {
				return [ 'success' => false, 'message' => 'Failed to update police station' ];
			}

			$updatedStation = $this->policeStationModel->findById($id);
			return [ 'success' => true, 'data' => $updatedStation, 'message' => 'Police station updated successfully' ];
		} catch (\Exception $e) {
			return [ 'success' => false, 'message' => 'Failed to update station: ' . $e->getMessage() ];
		}
	}

	public function deleteStation($id, $userId)
	{
		try {
			// Check if station exists
			$station = $this->policeStationModel->findById($id);
			if (!$station) {
				return [ 'success' => false, 'message' => 'Police station not found' ];
			}

			$success = $this->policeStationModel->delete($id);
			if (!$success) {
				return [ 'success' => false, 'message' => 'Failed to delete police station' ];
			}

			return [ 'success' => true, 'message' => 'Police station deleted successfully' ];
		} catch (\Exception $e) {
			return [ 'success' => false, 'message' => 'Failed to delete station: ' . $e->getMessage() ];
		}
	}

	public function getNearbyStations($lat, $lng, $radius = 50)
	{
		try {
			if (!is_numeric($lat) || !is_numeric($lng)) {
				return [ 'success' => false, 'message' => 'Invalid coordinates' ];
			}

			$stations = $this->policeStationModel->getNearbyStations($lat, $lng, $radius);
			return [ 'success' => true, 'data' => $stations ];
		} catch (\Exception $e) {
			return [ 'success' => false, 'message' => 'Failed to get nearby stations: ' . $e->getMessage() ];
		}
	}
}


