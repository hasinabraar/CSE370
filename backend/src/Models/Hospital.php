<?php

namespace App\Models;

use PDO;

class Hospital
{
    private $conn;
    private $table_name = "hospitals";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function getAll($filters = [])
    {
        $query = "SELECT * FROM " . $this->table_name;
        
        $whereConditions = [];
        $params = [];

        if (isset($filters['ambulance_available'])) {
            $whereConditions[] = "ambulance_available = :ambulance_available";
            $params[':ambulance_available'] = $filters['ambulance_available'];
        }

        if (!empty($filters['search'])) {
            $whereConditions[] = "(name LIKE :search OR address LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($whereConditions)) {
            $query .= " WHERE " . implode(' AND ', $whereConditions);
        }

        $query .= " ORDER BY name ASC";

        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById($id)
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $query = "INSERT INTO " . $this->table_name . " 
                  (name, latitude, longitude, ambulance_available, address, phone, emergency_contact) 
                  VALUES (:name, :latitude, :longitude, :ambulance_available, :address, :phone, :emergency_contact)";

        $stmt = $this->conn->prepare($query);
        $available = isset($data['ambulance_available']) ? (bool)$data['ambulance_available'] : true;
        $stmt->bindParam(":name", $data['name']);
        $stmt->bindParam(":latitude", $data['latitude']);
        $stmt->bindParam(":longitude", $data['longitude']);
        $stmt->bindParam(":ambulance_available", $available, PDO::PARAM_BOOL);
        $stmt->bindParam(":address", $data['address']);
        $stmt->bindParam(":phone", $data['phone']);
        $stmt->bindParam(":emergency_contact", $data['emergency_contact']);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function updateById($id, $data)
    {
        $query = "UPDATE " . $this->table_name . " 
                  SET name = :name, latitude = :latitude, longitude = :longitude, 
                      ambulance_available = :ambulance_available, address = :address, 
                      phone = :phone, emergency_contact = :emergency_contact, 
                      updated_at = CURRENT_TIMESTAMP 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $available = isset($data['ambulance_available']) ? (bool)$data['ambulance_available'] : false;
        $stmt->bindParam(":name", $data['name']);
        $stmt->bindParam(":latitude", $data['latitude']);
        $stmt->bindParam(":longitude", $data['longitude']);
        $stmt->bindParam(":ambulance_available", $available, PDO::PARAM_BOOL);
        $stmt->bindParam(":address", $data['address']);
        $stmt->bindParam(":phone", $data['phone']);
        $stmt->bindParam(":emergency_contact", $data['emergency_contact']);
        $stmt->bindParam(":id", $id);

        return $stmt->execute();
    }

    public function deleteById($id)
    {
        $stmt = $this->conn->prepare("DELETE FROM " . $this->table_name . " WHERE id = :id");
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }

    public function updateAmbulanceAvailability($id, $available)
    {
        $query = "UPDATE " . $this->table_name . " 
                  SET ambulance_available = :available, updated_at = CURRENT_TIMESTAMP 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":available", $available, PDO::PARAM_BOOL);
        $stmt->bindParam(":id", $id);

        return $stmt->execute();
    }

    public function getNearbyHospitals($lat, $lng, $radius = 50)
    {
        $query = "SELECT 
                    id,
                    name,
                    latitude,
                    longitude,
                    ambulance_available,
                    address,
                    phone,
                    (
                        6371 * acos(
                            cos(radians(:lat)) * 
                            cos(radians(latitude)) * 
                            cos(radians(longitude) - radians(:lng)) + 
                            sin(radians(:lat)) * 
                            sin(radians(latitude))
                        )
                    ) AS distance_km
                  FROM " . $this->table_name . "
                  HAVING distance_km <= :radius
                  ORDER BY distance_km ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":lat", $lat);
        $stmt->bindParam(":lng", $lng);
        $stmt->bindParam(":radius", $radius);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getHospitalActivity($hospitalId)
    {
        $query = "SELECT 
                    h.id,
                    h.name,
                    h.ambulance_available,
                    COUNT(a.id) as total_accidents,
                    COUNT(CASE WHEN a.status = 'pending' THEN 1 END) as pending_accidents,
                    COUNT(CASE WHEN a.status = 'in_progress' THEN 1 END) as active_accidents,
                    COUNT(CASE WHEN a.status = 'resolved' THEN 1 END) as resolved_accidents
                  FROM " . $this->table_name . " h
                  LEFT JOIN accidents a ON h.id = a.nearest_hospital_id
                  WHERE h.id = :hospital_id
                  GROUP BY h.id, h.name, h.ambulance_available";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":hospital_id", $hospitalId);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getHospitalNotifications($hospitalId, $filters = [])
    {
        $query = "SELECT 
                    n.*,
                    a.severity,
                    a.status as accident_status,
                    a.location_lat,
                    a.location_lng,
                    c.plate_number
                  FROM notifications n
                  JOIN accidents a ON n.accident_id = a.id
                  JOIN cars c ON a.car_id = c.id
                  WHERE n.hospital_id = :hospital_id";

        $whereConditions = [];
        $params = [':hospital_id' => $hospitalId];

        if (!empty($filters['status'])) {
            $whereConditions[] = "n.status = :notification_status";
            $params[':notification_status'] = $filters['status'];
        }

        if (!empty($filters['type'])) {
            $whereConditions[] = "n.notification_type = :type";
            $params[':type'] = $filters['type'];
        }

        if (!empty($whereConditions)) {
            $query .= " AND " . implode(' AND ', $whereConditions);
        }

        $query .= " ORDER BY n.sent_time DESC";

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

    public function markNotificationAsRead($notificationId, $hospitalId)
    {
        $query = "UPDATE notifications 
                  SET status = 'read', read_at = CURRENT_TIMESTAMP 
                  WHERE id = :notification_id AND hospital_id = :hospital_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":notification_id", $notificationId);
        $stmt->bindParam(":hospital_id", $hospitalId);

        return $stmt->execute();
    }
}
