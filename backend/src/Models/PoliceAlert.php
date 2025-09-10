<?php

namespace App\Models;

use PDO;

class PoliceAlert
{
	private $conn;
	private $table_name = "police_alerts";

	public function __construct($db)
	{
		$this->conn = $db;
	}

	public function create($data)
	{
		$query = "INSERT INTO " . $this->table_name . " 
				  (accident_id, police_station_id, message, status) 
				  VALUES (:accident_id, :police_station_id, :message, :status)";

		$stmt = $this->conn->prepare($query);
		$stmt->bindParam(":accident_id", $data['accident_id']);
		$stmt->bindParam(":police_station_id", $data['police_station_id']);
		$stmt->bindParam(":message", $data['message']);
		$status = $data['status'] ?? 'sent';
		$stmt->bindParam(":status", $status);

		if ($stmt->execute()) {
			return $this->conn->lastInsertId();
		}
		return false;
	}

	public function getAll($filters = [])
	{
		$query = "SELECT 
					pa.*, 
					ps.name as police_station_name,
					ps.jurisdiction,
					a.location_lat,
					a.location_lng,
					a.severity,
					c.plate_number,
					u.name as owner_name
				FROM police_alerts pa
				JOIN police_stations ps ON pa.police_station_id = ps.id
				JOIN accidents a ON pa.accident_id = a.id
				JOIN cars c ON a.car_id = c.id
				JOIN users u ON c.owner_id = u.id";

		$where = [];
		$params = [];

		if (!empty($filters['police_station_id'])) {
			$where[] = "pa.police_station_id = :police_station_id";
			$params[':police_station_id'] = $filters['police_station_id'];
		}

		if (!empty($filters['start_date'])) {
			$where[] = "DATE(pa.sent_time) >= :start_date";
			$params[':start_date'] = $filters['start_date'];
		}

		if (!empty($filters['end_date'])) {
			$where[] = "DATE(pa.sent_time) <= :end_date";
			$params[':end_date'] = $filters['end_date'];
		}

		if (!empty($where)) {
			$query .= " WHERE " . implode(' AND ', $where);
		}

		$query .= " ORDER BY pa.sent_time DESC";

		$stmt = $this->conn->prepare($query);
		foreach ($params as $k => $v) {
			$stmt->bindValue($k, $v);
		}
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function markAsRead($id, $policeStationId)
	{
		$query = "UPDATE " . $this->table_name . " 
				  SET status = 'read', read_at = CURRENT_TIMESTAMP 
				  WHERE id = :id AND police_station_id = :police_station_id";

		$stmt = $this->conn->prepare($query);
		$stmt->bindParam(":id", $id);
		$stmt->bindParam(":police_station_id", $policeStationId);

		return $stmt->execute();
	}

	public function getDailyCounts($startDate, $endDate)
	{
		$query = "SELECT DATE(sent_time) as day, COUNT(*) as total 
				FROM police_alerts 
				WHERE DATE(sent_time) BETWEEN :start AND :end 
				GROUP BY DATE(sent_time) ORDER BY day";
		$stmt = $this->conn->prepare($query);
		$stmt->bindParam(":start", $startDate);
		$stmt->bindParam(":end", $endDate);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
}


