<?php
require_once '../model/User.php';

class UserController {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new User();
    }
    
    public function getUserProfile($userId) {
        try {
            $userData = $this->userModel->getUserById($userId);
            $adoptedPets = $this->userModel->getApprovedPets($userId);
            
            if (!$userData) {
                return ['error' => 'User not found'];
            }
            
            return [
                'user' => $userData,
                'adoptedPets' => $adoptedPets
            ];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    public function updateProfile($userId, $data) {
        try {
            if (empty($data['name']) || empty($data['surname']) || empty($data['email'])) {
                return ['error' => 'Name, surname and email are required'];
            }
            
            if (!empty($data['new_password'])) {
                if (empty($data['current_password'])) {
                    return ['error' => 'Current password is required to set a new password'];
                }
                
                if (!$this->userModel->verifyPassword($userId, $data['current_password'])) {
                    return ['error' => 'Current password is incorrect'];
                }
            }
            
            $updateResult = $this->userModel->updateUser($userId, $data);
            
            if ($updateResult) {
                return ['success' => 'Profile updated successfully'];
            } else {
                return ['error' => 'Failed to update profile'];
            }
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function register($data) {
        try {
            $errors = [];
            
            if (empty($data['name'])) {
                $errors[] = "Name is required";
            }
            if (empty($data['surname'])) {
                $errors[] = "Surname is required";
            }
            if (empty($data['email'])) {
                $errors[] = "Email is required";
            } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Invalid email format";
            }
            if (empty($data['password'])) {
                $errors[] = "Password is required";
            }
            if (empty($data['confirm_password'])) {
                $errors[] = "Password confirmation is required";
            }
            if ($data['password'] !== $data['confirm_password']) {
                $errors[] = "Passwords do not match";
            }
            
            if (!empty($errors)) {
                return [
                    'success' => false,
                    'errors' => $errors
                ];
            }
            
            if ($this->userModel->emailExists($data['email'])) {
                return [
                    'success' => false,
                    'errors' => ['Email already exists']
                ];
            }
            
            $result = $this->userModel->createUser(
                $data['name'],
                $data['surname'],
                $data['email'],
                $data['password']
            );
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Registration successful'
                ];
            } else {
                return [
                    'success' => false,
                    'errors' => ['Failed to create account']
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'errors' => [$e->getMessage()]
            ];
        }
    }
} 