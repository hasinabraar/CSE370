<?php

namespace App\Middleware;

use App\Config\JWTConfig;

class AuthMiddleware
{
    private $jwtConfig;

    public function __construct(JWTConfig $jwtConfig)
    {
        $this->jwtConfig = $jwtConfig;
    }

    public function getTokenFromHeader()
    {
        $headers = getallheaders();
        
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }

    public function validateToken($token)
    {
        if (!$token) {
            throw new \Exception('No token provided');
        }

        return $this->jwtConfig->validateToken($token);
    }

    public function requireRole($requiredRole, $userRole)
    {
        if ($userRole !== $requiredRole && $userRole !== 'admin') {
            throw new \Exception('Insufficient permissions');
        }
    }

    public function requireAnyRole($requiredRoles, $userRole)
    {
        if (!in_array($userRole, $requiredRoles) && $userRole !== 'admin') {
            throw new \Exception('Insufficient permissions');
        }
    }
}
