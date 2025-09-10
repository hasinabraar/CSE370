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
}


