<?php
require_once '../config/database.php';

class User {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    public function __destruct() {
        if ($this->conn) {
            oci_close($this->conn);
        }
    }
    
    public function authenticate($name, $surname, $password) {
        $query = "SELECT * FROM users WHERE name = :name AND surname = :surname";
        $stmt = oci_parse($this->conn, $query);
        
        if (!$stmt) {

            throw new Exception("Could not parse query");
        }
        
        oci_bind_by_name($stmt, ":name", $name);
        oci_bind_by_name($stmt, ":surname", $surname);
        
        $execute = oci_execute($stmt);
        
        if (!$execute) {

            throw new Exception("Could not execute query");
        }
        
        $user = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);
        
        if (!$user) {

            return false;
        }
        
        if (!password_verify($password, $user['PASSWORD'])) {

            return false;
        }
        

        return $user;
    }
    
    public function createUser($name, $surname, $email, $password) {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $query = "INSERT INTO users (name, surname, email, password) VALUES (:name, :surname, :email, :password)";
            $stmt = oci_parse($this->conn, $query);
            
            if (!$stmt) {
                $error = oci_error($this->conn);

                throw new Exception("Database error while creating user");
            }
            
            oci_bind_by_name($stmt, ":name", $name);
            oci_bind_by_name($stmt, ":surname", $surname);
            oci_bind_by_name($stmt, ":email", $email);
            oci_bind_by_name($stmt, ":password", $hashedPassword);
            
            $execute = oci_execute($stmt);
            
            if (!$execute) {
                $error = oci_error($stmt);

                throw new Exception("Failed to create user: " . $error['message']);
            }
            
            oci_commit($this->conn);
            oci_free_statement($stmt);
            
            return true;
        } catch (Exception $e) {
            error_log("Error in createUser: " . $e->getMessage());
            if (isset($stmt)) {
                oci_free_statement($stmt);
            }
            throw $e;
        }
    }
    
    /**
     * Get user by ID
     */
    public function getUserById($id) {
        $query = "SELECT * FROM users WHERE id = :id";
        $stmt = oci_parse($this->conn, $query);
        
        if (!$stmt) {
            error_log("GetUserById failed: Could not parse query");
            throw new Exception("Could not parse query");
        }
        
        oci_bind_by_name($stmt, ":id", $id);
        
        $execute = oci_execute($stmt);
        
        if (!$execute) {
            error_log("GetUserById failed: Could not execute query");
            throw new Exception("Could not execute query");
        }
          $user = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);
        
        if ($user) {
            // Ensure consistent field casing
            return [
                'ID' => isset($user['ID']) ? (int)$user['ID'] : null,
                'NAME' => $user['NAME'] ?? '',
                'SURNAME' => $user['SURNAME'] ?? '',
                'EMAIL' => $user['EMAIL'] ?? '',
                'PASSWORD' => $user['PASSWORD'] ?? '',
                'LOCATION' => $user['LOCATION'] ?? '',
                'IS_FAMILY' => isset($user['IS_FAMILY']) ? (bool)$user['IS_FAMILY'] : false,
                'LATITUDE' => isset($user['LATITUDE']) ? (float)$user['LATITUDE'] : null,
                'LONGITUDE' => isset($user['LONGITUDE']) ? (float)$user['LONGITUDE'] : null,
                'ROL' => $user['ROL'] ?? ''
            ];
        }
        
        return null;
    }
    
    public function updateUser($id, $data) {
        $allowedFields = ['name', 'surname', 'email', 'password'];
        $updates = [];
        $values = [];
        
        foreach ($data as $field => $value) {
            if (in_array($field, $allowedFields) && !empty($value)) {
                if ($field === 'password') {
                    $value = password_hash($value, PASSWORD_DEFAULT);
                }
                $updates[] = "$field = :$field";
                $values[$field] = $value;
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $query = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = oci_parse($this->conn, $query);
        
        if (!$stmt) {
            throw new Exception("Could not parse query");
        }
        
        foreach ($values as $field => $value) {
            oci_bind_by_name($stmt, ":$field", $value);
        }
        oci_bind_by_name($stmt, ":id", $id);
        
        $execute = oci_execute($stmt);
        oci_free_statement($stmt);
        
        return $execute;
    }
    
    public function deleteUser($id) {
        $query = "DELETE FROM users WHERE id = :id";
        $stmt = oci_parse($this->conn, $query);
        
        if (!$stmt) {
            throw new Exception("Could not parse query");
        }
        
        oci_bind_by_name($stmt, ":id", $id);
        
        $execute = oci_execute($stmt);
        oci_free_statement($stmt);
        
        return $execute;
    }

    public function getApprovedPets($userId) {
        $query = "WITH FirstApproval AS (
                    SELECT pet_id,
                           MIN(adoption_date) as first_approval_date
                    FROM adoptions
                    WHERE adopter_id = :user_id
                    AND status = 'approved'
                    GROUP BY pet_id
                )
                SELECT DISTINCT 
                    p.id,
                    p.name,
                    p.species,
                    p.breed,
                    p.age,
                    p.gender,
                    fa.first_approval_date as ADOPTION_DATE,
                    COALESCE(
                        (SELECT m.url 
                         FROM media m 
                         WHERE m.pet_id = p.id 
                         AND ROWNUM = 1),
                        'stiluri/imagini/' || LOWER(p.species) || '.png'
                    ) as PET_IMAGE
                FROM pets p
                JOIN FirstApproval fa ON p.id = fa.pet_id
                ORDER BY fa.first_approval_date DESC";
                 
        $stmt = oci_parse($this->conn, $query);
        
        if (!$stmt) {
            throw new Exception("Could not parse query");
        }
        
        oci_bind_by_name($stmt, ":user_id", $userId);
        
        $execute = oci_execute($stmt);
        
        if (!$execute) {
            $e = oci_error($stmt);
            throw new Exception("Could not execute query: " . $e['message']);
        }
        
        $pets = array();
        while ($row = oci_fetch_assoc($stmt)) {
            $pets[] = $row;
        }
        
        oci_free_statement($stmt);
        
        return $pets;
    }
}