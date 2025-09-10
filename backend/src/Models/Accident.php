<?php

namespace App\Models;

use PDO;

class Accident
{
    private $conn;
    private $table_name = "accidents";
    private $policeAlertModel;
    private $logFile;

    public function __construct($db)
    {
        $this->conn = $db;
        // Lazy include to avoid circular deps in autoload
        if (class_exists('App\\Models\\PoliceAlert')) {
            $this->policeAlertModel = new \App\Models\PoliceAlert($db);
        }
        $this->logFile = __DIR__ . '/../../storage/logs/app.log';
    }

    public function create($data)
    {
        // Find nearest hospital with available ambulance
        $nearestHospital = $this->findNearestHospital($data['location_lat'], $data['location_lng']);
        $nearestHospitalId = $nearestHospital['id'] ?? null;
        $estimatedDistance = isset($nearestHospital['distance_km']) ? round((float)$nearestHospital['distance_km'], 2) : null;

        $this->debugLog('Accident.create input', [
            'data' => $data,
            'nearestHospital' => $nearestHospital,
            'nearestHospitalId' => $nearestHospitalId,
            'estimatedDistance' => $estimatedDistance
        ]);
        
        $query = "INSERT INTO " . $this->table_name . " 
                  (car_id, location_lat, location_lng, accident_time, severity, status, description, nearest_hospital_id, estimated_distance) 
                  VALUES (:car_id, :location_lat, :location_lng, :accident_time, :severity, :status, :description, :nearest_hospital_id, :estimated_distance)";

        $stmt = $this->conn->prepare($query);

        // Use bindValue for direct values instead of bindParam on array offsets
        $stmt->bindValue(":car_id", (int)$data['car_id'], PDO::PARAM_INT);
        $stmt->bindValue(":location_lat", $data['location_lat']);
        $stmt->bindValue(":location_lng", $data['location_lng']);
        $stmt->bindValue(":accident_time", $data['accident_time'] ?? date('Y-m-d H:i:s'));
        $stmt->bindValue(":severity", $data['severity']);
        $stmt->bindValue(":status", $data['status']);
        $stmt->bindValue(":description", $data['description'] ?? '');
        if ($nearestHospitalId === null) {
            $stmt->bindValue(":nearest_hospital_id", null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(":nearest_hospital_id", (int)$nearestHospitalId, PDO::PARAM_INT);
        }
        if ($estimatedDistance === null) {
            $stmt->bindValue(":estimated_distance", null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(":estimated_distance", $estimatedDistance);
        }

        $execOk = $stmt->execute();
        if ($execOk) {
            $accidentId = $this->conn->lastInsertId();
            
            // Create notification for nearest hospital
            if ($nearestHospitalId !== null) {
                $this->createNotification($accidentId, $nearestHospitalId, $data);
            }
            
            // Create police alert to nearest police station (best-effort)
            try {
                $nearestPolice = $this->findNearestPoliceStation($data['location_lat'], $data['location_lng']);
                if ($nearestPolice && $this->policeAlertModel) {
                    $this->policeAlertModel->create([
                        'accident_id' => $accidentId,
                        'police_station_id' => $nearestPolice['id'],
                        'message' => 'Accident reported. Plate ' . ($data['plate_number'] ?? '') . ', Severity: ' . ucfirst($data['severity']),
                        'status' => 'sent'
                    ]);
                }
            } catch (\Throwable $e) {
                // no-op
            }
            
            return $accidentId;
        }

        $this->debugLog('Accident.create execute failed', [
            'pdo_error' => $stmt->errorInfo()
        ]);
        return false;
    }

    public function findById($id)
    {
        $query = "SELECT a.*, c.plate_number, u.name as owner_name, h.name as hospital_name 
                  FROM " . $this->table_name . " a
                  JOIN cars c ON a.car_id = c.id
                  JOIN users u ON c.owner_id = u.id
                  LEFT JOIN hospitals h ON a.nearest_hospital_id = h.id
                  WHERE a.id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAll($filters = [])
    {
        $query = "SELECT a.*, c.plate_number, u.name as owner_name, h.name as hospital_name 
                  FROM " . $this->table_name . " a
                  JOIN cars c ON a.car_id = c.id
                  JOIN users u ON c.owner_id = u.id
                  LEFT JOIN hospitals h ON a.nearest_hospital_id = h.id";

        $whereConditions = [];
        $params = [];

        // Apply filters
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

        if (!empty($filters['car_id'])) {
            $whereConditions[] = "a.car_id = :car_id";
            $params[':car_id'] = $filters['car_id'];
        }

        if (!empty($filters['hospital_id'])) {
            $whereConditions[] = "a.nearest_hospital_id = :hospital_id";
            $params[':hospital_id'] = $filters['hospital_id'];
        }

        if (!empty($whereConditions)) {
            $query .= " WHERE " . implode(' AND ', $whereConditions);
        }

        // Apply sorting
        $sortField = $filters['sort_by'] ?? 'accident_time';
        $sortOrder = $filters['sort_order'] ?? 'DESC';
        $query .= " ORDER BY a." . $sortField . " " . $sortOrder;

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

    public function update($id, $data, $userId)
    {
        $oldStatus = $this->getCurrentStatus($id);
        
        $query = "UPDATE " . $this->table_name . " 
                  SET status = :status, description = :description, updated_at = CURRENT_TIMESTAMP 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":status", $data['status']);
        $stmt->bindParam(":description", $data['description']);
        $stmt->bindParam(":id", $id);

        if ($stmt->execute()) {
            // Log status change
            $this->logStatusChange($id, $oldStatus, $data['status'], $userId, $data['change_reason'] ?? '');
            return true;
        }

        return false;
    }

    public function getStatistics($filters = [])
    {
        $query = "SELECT 
                    COUNT(*) as total_accidents,
                    COUNT(CASE WHEN severity = 'low' THEN 1 END) as low_severity,
                    COUNT(CASE WHEN severity = 'medium' THEN 1 END) as medium_severity,
                    COUNT(CASE WHEN severity = 'high' THEN 1 END) as high_severity,
                    COUNT(CASE WHEN severity = 'critical' THEN 1 END) as critical_severity,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
                    COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress,
                    COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved,
                    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled
                  FROM " . $this->table_name;

        $whereConditions = [];
        $params = [];

        if (!empty($filters['start_date'])) {
            $whereConditions[] = "DATE(accident_time) >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $whereConditions[] = "DATE(accident_time) <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }

        if (!empty($whereConditions)) {
            $query .= " WHERE " . implode(' AND ', $whereConditions);
        }

        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getDailyStats($startDate, $endDate)
    {
        $query = "SELECT 
                    DATE(accident_time) as accident_date,
                    COUNT(*) as total_accidents,
                    COUNT(CASE WHEN severity = 'low' THEN 1 END) as low_severity,
                    COUNT(CASE WHEN severity = 'medium' THEN 1 END) as medium_severity,
                    COUNT(CASE WHEN severity = 'high' THEN 1 END) as high_severity,
                    COUNT(CASE WHEN severity = 'critical' THEN 1 END) as critical_severity
                  FROM " . $this->table_name . "
                  WHERE DATE(accident_time) BETWEEN :start_date AND :end_date
                  GROUP BY DATE(accident_time)
                  ORDER BY accident_date";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":start_date", $startDate);
        $stmt->bindParam(":end_date", $endDate);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function findNearestHospital($lat, $lng)
    {
        $query = "SELECT 
                    id,
                    name,
                    latitude,
                    longitude,
                    (
                        6371 * acos(
                            cos(radians(?)) * 
                            cos(radians(latitude)) * 
                            cos(radians(longitude) - radians(?)) + 
                            sin(radians(?)) * 
                            sin(radians(latitude))
                        )
                    ) AS distance_km
                  FROM hospitals
                  WHERE ambulance_available = TRUE
                  ORDER BY distance_km ASC
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$lat, $lng, $lat]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            // If no hospital with ambulance available, get the nearest one
            $query = "SELECT 
                        id,
                        name,
                        latitude,
                        longitude,
                        (
                            6371 * acos(
                                cos(radians(?)) * 
                                cos(radians(latitude)) * 
                                cos(radians(longitude) - radians(?)) + 
                                sin(radians(?)) * 
                                sin(radians(latitude))
                            )
                        ) AS distance_km
                      FROM hospitals
                      ORDER BY distance_km ASC
                      LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->execute([$lat, $lng, $lat]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        return $result;
    }

    private function findNearestPoliceStation($lat, $lng)
    {
        $query = "SELECT 
                        id,
                        name,
                        latitude,
                        longitude,
                        (
                            6371 * acos(
                                cos(radians(?)) * 
                                cos(radians(latitude)) * 
                                cos(radians(longitude) - radians(?)) + 
                                sin(radians(?)) * 
                                sin(radians(latitude))
                            )
                        ) AS distance_km
                  FROM police_stations
                  ORDER BY distance_km ASC
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$lat, $lng, $lat]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function createNotification($accidentId, $hospitalId, $accidentData)
    {
        $message = "New accident reported near your location. Severity: " . ucfirst($accidentData['severity']);
        
        $query = "INSERT INTO notifications (accident_id, hospital_id, message, notification_type) 
                  VALUES (:accident_id, :hospital_id, :message, 'alert')";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":accident_id", $accidentId);
        $stmt->bindParam(":hospital_id", $hospitalId);
        $stmt->bindParam(":message", $message);

        return $stmt->execute();
    }

    private function getCurrentStatus($accidentId)
    {
        $query = "SELECT status FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $accidentId);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['status'] : null;
    }

    private function logStatusChange($accidentId, $oldStatus, $newStatus, $userId, $reason)
    {
        $query = "INSERT INTO accident_status_history 
                  (accident_id, old_status, new_status, changed_by, change_reason) 
                  VALUES (:accident_id, :old_status, :new_status, :changed_by, :change_reason)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":accident_id", $accidentId);
        $stmt->bindParam(":old_status", $oldStatus);
        $stmt->bindParam(":new_status", $newStatus);
        $stmt->bindParam(":changed_by", $userId);
        $stmt->bindParam(":change_reason", $reason);

        return $stmt->execute();
    }

    private function debugLog($message, array $context = [])
    {
        try {
            $dir = dirname($this->logFile);
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
            $line = date('Y-m-d H:i:s') . ' [Accident] ' . $message . ' ' . json_encode($context) . PHP_EOL;
            @file_put_contents($this->logFile, $line, FILE_APPEND);
        } catch (\Throwable $e) {
            // ignore logging errors
        }
    }
}
