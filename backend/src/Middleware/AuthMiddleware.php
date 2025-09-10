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
        $headers = function_exists('getallheaders') ? getallheaders() : [];

        // Normalize header keys to lowercase for robust lookup
        $normalized = [];
        foreach ($headers as $k => $v) {
            $normalized[strtolower($k)] = $v;
        }

        // Also check $_SERVER fallback commonly used by some SAPIs/proxies
        if (isset($_SERVER['HTTP_AUTHORIZATION']) && !isset($normalized['authorization'])) {
            $normalized['authorization'] = $_SERVER['HTTP_AUTHORIZATION'];
        }

        if (isset($normalized['authorization'])) {
            $authHeader = $normalized['authorization'];
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
