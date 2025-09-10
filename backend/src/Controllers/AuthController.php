<?php

namespace App\Controllers;

use App\Models\User;
use App\Config\JWTConfig;

class AuthController
{
    private $pdo;
    private $jwtConfig;
    private $userModel;

    public function __construct($pdo, JWTConfig $jwtConfig)
    {
        $this->pdo = $pdo;
        $this->jwtConfig = $jwtConfig;
        $this->userModel = new User($pdo);
    }

    public function register($data)
    {
        try {
            // Validate required fields
            $requiredFields = ['name', 'email', 'password', 'role'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    return [
                        'success' => false,
                        'message' => "Field '$field' is required"
                    ];
                }
            }

            // Validate email format
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return [
                    'success' => false,
                    'message' => 'Invalid email format'
                ];
            }

            // Validate role
            $validRoles = ['user', 'hospital', 'admin'];
            if (!in_array($data['role'], $validRoles)) {
                return [
                    'success' => false,
                    'message' => 'Invalid role. Must be one of: ' . implode(', ', $validRoles)
                ];
            }

            // Check if email already exists
            $existingUser = $this->userModel->findByEmail($data['email']);
            if ($existingUser) {
                return [
                    'success' => false,
                    'message' => 'Email already registered'
                ];
            }

            // Create user
            $userId = $this->userModel->create($data);
            if (!$userId) {
                return [
                    'success' => false,
                    'message' => 'Failed to create user'
                ];
            }

            // Get created user
            $user = $this->userModel->findById($userId);

            // Generate JWT token
            $token = $this->jwtConfig->generateToken([
                'user_id' => $user['id'],
                'email' => $user['email'],
                'role' => $user['role']
            ]);

            return [
                'success' => true,
                'message' => 'User registered successfully',
                'data' => [
                    'user' => $user,
                    'token' => $token
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage()
            ];
        }
    }

    public function login($data)
    {
        try {
            // Validate required fields
            if (empty($data['email']) || empty($data['password'])) {
                return [
                    'success' => false,
                    'message' => 'Email and password are required'
                ];
            }

            // Find user by email
            $user = $this->userModel->findByEmail($data['email']);
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Invalid email or password'
                ];
            }

            // Verify password
            if (!$this->userModel->verifyPassword($data['password'], $user['password_hash'])) {
                return [
                    'success' => false,
                    'message' => 'Invalid email or password'
                ];
            }

            // Generate JWT token
            $token = $this->jwtConfig->generateToken([
                'user_id' => $user['id'],
                'email' => $user['email'],
                'role' => $user['role']
            ]);

            // Remove password from response
            unset($user['password_hash']);

            return [
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => $user,
                    'token' => $token
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Login failed: ' . $e->getMessage()
            ];
        }
    }

    public function getProfile($userId)
    {
        try {
            $user = $this->userModel->findById($userId);
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not found'
                ];
            }

            return [
                'success' => true,
                'data' => $user
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to get profile: ' . $e->getMessage()
            ];
        }
    }

    public function updateProfile($userId, $data)
    {
        try {
            // Validate required fields
            if (empty($data['name'])) {
                return [
                    'success' => false,
                    'message' => 'Name is required'
                ];
            }

            // Update user
            $success = $this->userModel->update($userId, $data);
            if (!$success) {
                return [
                    'success' => false,
                    'message' => 'Failed to update profile'
                ];
            }

            // Get updated user
            $user = $this->userModel->findById($userId);

            return [
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => $user
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update profile: ' . $e->getMessage()
            ];
        }
    }

    public function changePassword($userId, $data)
    {
        try {
            // Validate required fields
            if (empty($data['current_password']) || empty($data['new_password'])) {
                return [
                    'success' => false,
                    'message' => 'Current password and new password are required'
                ];
            }

            // Get current user with password hash
            $user = $this->userModel->findByIdWithPassword($userId);
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not found'
                ];
            }

            // Verify current password
            if (!$this->userModel->verifyPassword($data['current_password'], $user['password_hash'])) {
                return [
                    'success' => false,
                    'message' => 'Current password is incorrect'
                ];
            }

            // Validate new password
            if (strlen($data['new_password']) < 6) {
                return [
                    'success' => false,
                    'message' => 'New password must be at least 6 characters long'
                ];
            }

            // Update password
            $success = $this->userModel->updatePassword($userId, $data['new_password']);
            if (!$success) {
                return [
                    'success' => false,
                    'message' => 'Failed to update password'
                ];
            }

            return [
                'success' => true,
                'message' => 'Password updated successfully'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to change password: ' . $e->getMessage()
            ];
        }
    }

    public function getAllUsers($filters = [])
    {
        try {
            $users = $this->userModel->getAll($filters);

            return [
                'success' => true,
                'data' => $users
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to get users: ' . $e->getMessage()
            ];
        }
    }
}
