<?php

namespace App\Controllers;

use App\Models\Hospital;
use App\Models\PoliceStation;
use App\Models\User;
use App\Config\JWTConfig;

class AdminController
{
    private $pdo;
    private $jwtConfig;
    private $hospitalModel;
    private $policeStationModel;
    private $userModel;

    public function __construct($pdo, JWTConfig $jwtConfig)
    {
        $this->pdo = $pdo;
        $this->jwtConfig = $jwtConfig;
        $this->hospitalModel = new Hospital($pdo);
        $this->policeStationModel = new PoliceStation($pdo);
        $this->userModel = new User($pdo);
    }

    private function requireAdmin($user)
    {
        if (($user['role'] ?? null) !== 'admin') {
            throw new \Exception('Insufficient permissions');
        }
    }

    // Hospitals CRUD
    public function createHospital($data)
    {
        $id = $this->hospitalModel->create($data);
        return $id ? ['success' => true, 'data' => $this->hospitalModel->findById($id)] : ['success' => false, 'message' => 'Failed to create hospital'];
    }
    public function updateHospital($id, $data)
    {
        $ok = $this->hospitalModel->updateById($id, $data);
        return $ok ? ['success' => true, 'data' => $this->hospitalModel->findById($id)] : ['success' => false, 'message' => 'Failed to update hospital'];
    }
    public function deleteHospital($id)
    {
        $ok = $this->hospitalModel->deleteById($id);
        return $ok ? ['success' => true] : ['success' => false, 'message' => 'Failed to delete hospital'];
    }

    // Police stations CRUD
    public function createPoliceStation($data)
    {
        $id = $this->policeStationModel->create($data);
        return $id ? ['success' => true, 'data' => $this->policeStationModel->findById($id)] : ['success' => false, 'message' => 'Failed to create police station'];
    }
    public function updatePoliceStation($id, $data)
    {
        $ok = $this->policeStationModel->update($id, $data);
        return $ok ? ['success' => true, 'data' => $this->policeStationModel->findById($id)] : ['success' => false, 'message' => 'Failed to update police station'];
    }
    public function deletePoliceStation($id)
    {
        $ok = $this->policeStationModel->delete($id);
        return $ok ? ['success' => true] : ['success' => false, 'message' => 'Failed to delete police station'];
    }
}


