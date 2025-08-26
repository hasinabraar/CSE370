<?php

namespace App\Config;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWTConfig
{
    private $secret_key;
    private $algorithm;

    public function __construct()
    {
        $this->secret_key = $_ENV['JWT_SECRET'] ?? 'your-secret-key-change-in-production';
        $this->algorithm = 'HS256';
    }

    public function generateToken($payload)
    {
        $issued_at = time();
        $expiration_time = $issued_at + (60 * 60 * 24); // 24 hours

        $token_payload = [
            'iat' => $issued_at,
            'exp' => $expiration_time,
            'user_id' => $payload['user_id'],
            'email' => $payload['email'],
            'role' => $payload['role']
        ];

        return JWT::encode($token_payload, $this->secret_key, $this->algorithm);
    }

    public function validateToken($token)
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret_key, $this->algorithm));
            return (array) $decoded;
        } catch (\Exception $e) {
            throw new \Exception('Invalid token: ' . $e->getMessage());
        }
    }

    public function getSecretKey()
    {
        return $this->secret_key;
    }

    public function getAlgorithm()
    {
        return $this->algorithm;
    }
}
