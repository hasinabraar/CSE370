<?php

namespace App\Models;

use PDO;

class Car
{
    private $conn;
    private $table_name = "cars";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function create($data)
    {
        $query = "INSERT INTO " . $this->table_name . " 
                  (owner_id, plate_number, sensor_status, model, year) 
                  VALUES (:owner_id, :plate_number, :sensor_status, :model, :year)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":owner_id", $data['owner_id']);
        $stmt->bindParam(":plate_number", $data['plate_number']);
        $stmt->bindParam(":sensor_status", $data['sensor_status']);
        $stmt->bindParam(":model", $data['model']);
        $stmt->bindParam(":year", $data['year']);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }

        return false;
    }

    public function getUserCars($userId)
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE owner_id = :owner_id ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":owner_id", $userId);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById($id)
    {
        $query = "SELECT c.*, u.name as owner_name 
                  FROM " . $this->table_name . " c
                  JOIN users u ON c.owner_id = u.id
                  WHERE c.id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateSensorStatus($id, $status, $userId)
    {
        $query = "UPDATE " . $this->table_name . " 
                  SET sensor_status = :sensor_status, updated_at = CURRENT_TIMESTAMP 
                  WHERE id = :id AND owner_id = :owner_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":sensor_status", $status);
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":owner_id", $userId);

        return $stmt->execute();
    }

    public function update($id, $data, $userId)
    {
        $query = "UPDATE " . $this->table_name . " 
                  SET plate_number = :plate_number, 
                      model = :model, 
                      year = :year, 
                      sensor_status = :sensor_status, 
                      updated_at = CURRENT_TIMESTAMP 
                  WHERE id = :id AND owner_id = :owner_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":plate_number", $data['plate_number']);
        $stmt->bindParam(":model", $data['model']);
        $stmt->bindParam(":year", $data['year']);
        $stmt->bindParam(":sensor_status", $data['sensor_status']);
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":owner_id", $userId);

        return $stmt->execute();
    }

    public function checkPlateNumberExists($plateNumber, $excludeId = null)
    {
        $query = "SELECT id FROM " . $this->table_name . " WHERE plate_number = :plate_number";
        
        if ($excludeId) {
            $query .= " AND id != :exclude_id";
        }
        
        $query .= " LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":plate_number", $plateNumber);
        
        if ($excludeId) {
            $stmt->bindParam(":exclude_id", $excludeId);
        }
        
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ? true : false;
    }

    public function getCarAccidents($carId, $filters = [])
    {
        $query = "SELECT a.*, h.name as hospital_name 
                  FROM accidents a
                  LEFT JOIN hospitals h ON a.nearest_hospital_id = h.id
                  WHERE a.car_id = :car_id";

        $whereConditions = [];
        $params = [':car_id' => $carId];

        if (!empty($filters['status'])) {
            $whereConditions[] = "a.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['severity'])) {
            $whereConditions[] = "a.severity = :severity";
            $params[':severity'] = $filters['severity'];
        }

        if (!empty($filters['start_date'])) {
            $whereConditions[] = "DATE(a.accident_time) >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $whereConditions[] = "DATE(a.accident_time) <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }

        if (!empty($whereConditions)) {
            $query .= " AND " . implode(' AND ', $whereConditions);
        }

        $query .= " ORDER BY a.accident_time DESC";

        // Add pagination
        if (isset($filters['page']) && isset($filters['limit'])) {
            $offset = ($filters['page'] - 1) * $filters['limit'];
            $query .= " LIMIT :limit OFFSET :offset";
            $params[':limit'] = (int)$filters['limit'];
            $params[':offset'] = (int)$offset;
        }

        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function delete($id, $userId)
    {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id AND owner_id = :owner_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":owner_id", $userId);

        return $stmt->execute();
    }

    public function getAll($filters = [])
    {
        $query = "SELECT c.*, u.name as owner_name 
                  FROM " . $this->table_name . " c
                  JOIN users u ON c.owner_id = u.id";

        $whereConditions = [];
        $params = [];

        if (!empty($filters['sensor_status'])) {
            $whereConditions[] = "c.sensor_status = :sensor_status";
            $params[':sensor_status'] = $filters['sensor_status'];
        }

        if (!empty($filters['owner_id'])) {
            $whereConditions[] = "c.owner_id = :owner_id";
            $params[':owner_id'] = $filters['owner_id'];
        }

        if (!empty($filters['search'])) {
            $whereConditions[] = "(c.plate_number LIKE :search OR c.model LIKE :search OR u.name LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($whereConditions)) {
            $query .= " WHERE " . implode(' AND ', $whereConditions);
        }

        $query .= " ORDER BY c.created_at DESC";

        // Add pagination
        if (isset($filters['page']) && isset($filters['limit'])) {
            $offset = ($filters['page'] - 1) * $filters['limit'];
            $query .= " LIMIT :limit OFFSET :offset";
            $params[':limit'] = (int)$filters['limit'];
            $params[':offset'] = (int)$offset;
        }

        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
