<?php

namespace App\Models;

use PDO;

class User
{
    private $conn;
    private $table_name = "users";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function create($data)
    {
        $query = "INSERT INTO " . $this->table_name . " 
                  (name, email, password_hash, role, phone) 
                  VALUES (:name, :email, :password_hash, :role, :phone)";

        $stmt = $this->conn->prepare($query);

        $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);

        $stmt->bindParam(":name", $data['name']);
        $stmt->bindParam(":email", $data['email']);
        $stmt->bindParam(":password_hash", $password_hash);
        $stmt->bindParam(":role", $data['role']);
        $stmt->bindParam(":phone", $data['phone']);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }

        return false;
    }

    public function findByEmail($email)
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findById($id)
    {
        $query = "SELECT id, name, email, role, phone, created_at FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }

    public function update($id, $data)
    {
        $query = "UPDATE " . $this->table_name . " 
                  SET name = :name, phone = :phone, updated_at = CURRENT_TIMESTAMP 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":name", $data['name']);
        $stmt->bindParam(":phone", $data['phone']);
        $stmt->bindParam(":id", $id);

        return $stmt->execute();
    }

    public function getAll($filters = [])
    {
        $query = "SELECT id, name, email, role, phone, created_at FROM " . $this->table_name;
        
        $whereConditions = [];
        $params = [];

        if (!empty($filters['role'])) {
            $whereConditions[] = "role = :role";
            $params[':role'] = $filters['role'];
        }

        if (!empty($filters['search'])) {
            $whereConditions[] = "(name LIKE :search OR email LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($whereConditions)) {
            $query .= " WHERE " . implode(' AND ', $whereConditions);
        }

        $query .= " ORDER BY created_at DESC";

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
