<?php

namespace App\Models;

use PDO;

class PoliceStation
{
	private $conn;
	private $table_name = "police_stations";

	public function __construct($db)
	{
		$this->conn = $db;
	}

	public function getAll($filters = [])
	{
		$query = "SELECT * FROM " . $this->table_name;

		$whereConditions = [];
		$params = [];

		if (!empty($filters['search'])) {
			$whereConditions[] = "(name LIKE :search OR jurisdiction LIKE :search OR address LIKE :search)";
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
				  (name, jurisdiction, latitude, longitude, address, phone) 
				  VALUES (:name, :jurisdiction, :latitude, :longitude, :address, :phone)";

		$stmt = $this->conn->prepare($query);
		$stmt->bindParam(":name", $data['name']);
		$stmt->bindParam(":jurisdiction", $data['jurisdiction']);
		$stmt->bindParam(":latitude", $data['latitude']);
		$stmt->bindParam(":longitude", $data['longitude']);
		$stmt->bindParam(":address", $data['address']);
		$stmt->bindParam(":phone", $data['phone']);

		if ($stmt->execute()) {
			return $this->conn->lastInsertId();
		}

		return false;
	}

	public function update($id, $data)
	{
		$query = "UPDATE " . $this->table_name . " 
				  SET name = :name, jurisdiction = :jurisdiction, latitude = :latitude, 
				      longitude = :longitude, address = :address, phone = :phone, 
				      updated_at = CURRENT_TIMESTAMP 
				  WHERE id = :id";

		$stmt = $this->conn->prepare($query);
		$stmt->bindParam(":name", $data['name']);
		$stmt->bindParam(":jurisdiction", $data['jurisdiction']);
		$stmt->bindParam(":latitude", $data['latitude']);
		$stmt->bindParam(":longitude", $data['longitude']);
		$stmt->bindParam(":address", $data['address']);
		$stmt->bindParam(":phone", $data['phone']);
		$stmt->bindParam(":id", $id);

		return $stmt->execute();
	}

	public function delete($id)
	{
		$query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
		$stmt = $this->conn->prepare($query);
		$stmt->bindParam(":id", $id);

		return $stmt->execute();
	}

	public function getNearbyStations($lat, $lng, $radius = 50)
	{
		$query = "SELECT 
					id,
					name,
					jurisdiction,
					latitude,
					longitude,
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
}


