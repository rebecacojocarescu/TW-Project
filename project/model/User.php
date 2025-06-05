<?php
    require_once __DIR__ . '/../config/database.php';

    class User{
        private $conn;

        public function __construct(){
            $this->conn = getConnection();
        }

        public function createUser($name, $surname, $email, $password){
            try {
                $sql = "INSERT INTO users (name, surname, email, password) VALUES (:name, :surname, :email, :password)";
                $stmt = oci_parse($this->conn, $sql);
                
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                oci_bind_by_name($stmt, ':name', $name);
                oci_bind_by_name($stmt, ':surname', $surname);
                oci_bind_by_name($stmt, ':email', $email);
                oci_bind_by_name($stmt, ':password', $hashed_password);

                $result = oci_execute($stmt);
                oci_free_statement($stmt);
                
                error_log("User creation attempt - Name: $name, Surname: $surname, Email: $email - Result: " . ($result ? "Success" : "Failed"));
                
                return $result;
            } catch (Exception $e) {
                error_log("Error creating user: " . $e->getMessage());
                return false;
            }
        }

        public function authenticate($name, $surname, $password){
            try {
                $sql = "SELECT id, password FROM users WHERE name = :name AND surname = :surname";
                $stmt = oci_parse($this->conn, $sql);
                
                oci_bind_by_name($stmt, ':name', $name);
                oci_bind_by_name($stmt, ':surname', $surname);
                
                if (!oci_execute($stmt)) {
                    $e = oci_error($stmt);
                    error_log("Database error during authentication: " . $e['message']);
                    return false;
                }

                $row = oci_fetch_assoc($stmt);
                oci_free_statement($stmt);

                if (!$row) {
                    error_log("Authentication failed - User not found - Name: $name, Surname: $surname");
                    return false;
                }

                $verified = password_verify($password, $row['PASSWORD']);
                error_log("Authentication attempt - Name: $name, Surname: $surname - Result: " . ($verified ? "Success" : "Failed"));
                
                if ($verified) {
                    return ['id' => $row['ID']];
                }
                
                return false;
            } catch (Exception $e) {
                error_log("Error during authentication: " . $e->getMessage());
                return false;
            }
        }

        public function getUserById($userId) {
            try {
                $query = "SELECT * FROM users WHERE id = :id";
                $stmt = oci_parse($this->conn, $query);
                oci_bind_by_name($stmt, ":id", $userId);
                
                if (!oci_execute($stmt)) {
                    throw new Exception("Failed to fetch user data");
                }
                
                $user = oci_fetch_assoc($stmt);
                oci_free_statement($stmt);
                
                return $user;
            } catch (Exception $e) {
                error_log("Error getting user by ID: " . $e->getMessage());
                return false;
            }
        }

        public function updateUser($userId, $data) {
            try {
                $query = "UPDATE users SET 
                         name = :name,
                         surname = :surname,
                         email = :email,
                         location = :location" .
                         (!empty($data['new_password']) ? ", password = :password" : "") .
                         " WHERE id = :id";
                
                $stmt = oci_parse($this->conn, $query);
                
                oci_bind_by_name($stmt, ":name", $data['name']);
                oci_bind_by_name($stmt, ":surname", $data['surname']);
                oci_bind_by_name($stmt, ":email", $data['email']);
                oci_bind_by_name($stmt, ":location", $data['location']);
                oci_bind_by_name($stmt, ":id", $userId);
                
                if (!empty($data['new_password'])) {
                    $password = password_hash($data['new_password'], PASSWORD_DEFAULT);
                    oci_bind_by_name($stmt, ":password", $password);
                }
                
                $result = oci_execute($stmt);
                oci_free_statement($stmt);
                
                return $result;
            } catch (Exception $e) {
                error_log("Error updating user: " . $e->getMessage());
                return false;
            }
        }

        public function verifyPassword($userId, $password) {
            try {
                $query = "SELECT password FROM users WHERE id = :id";
                $stmt = oci_parse($this->conn, $query);
                oci_bind_by_name($stmt, ":id", $userId);
                
                if (!oci_execute($stmt)) {
                    throw new Exception("Failed to verify password");
                }
                
                $user = oci_fetch_assoc($stmt);
                oci_free_statement($stmt);
                
                if (!$user) {
                    return false;
                }
                
                return password_verify($password, $user['PASSWORD']);
            } catch (Exception $e) {
                error_log("Error verifying password: " . $e->getMessage());
                return false;
            }
        }

        public function getApprovedPets($userId) {
            try {
                $query = "DECLARE
                            v_result SYS_REFCURSOR;
                         BEGIN
                            :result := get_approved_pet_image(:user_id);
                         END;";
                
                $stmt = oci_parse($this->conn, $query);
                $result = oci_new_cursor($this->conn);
                
                oci_bind_by_name($stmt, ":result", $result, -1, SQLT_RSET);
                oci_bind_by_name($stmt, ":user_id", $userId);
                
                oci_execute($stmt);
                oci_execute($result);
                
                $pets = [];
                while ($row = oci_fetch_assoc($result)) {
                    $pets[] = $row;
                }
                
                oci_free_statement($result);
                oci_free_statement($stmt);
                
                return $pets;
            } catch (Exception $e) {
                error_log("Error getting approved pets: " . $e->getMessage());
                return [];
            }
        }

        public function emailExists($email) {
            try {
                $query = "SELECT COUNT(*) as count FROM users WHERE email = :email";
                $stmt = oci_parse($this->conn, $query);
                oci_bind_by_name($stmt, ":email", $email);
                
                if (!oci_execute($stmt)) {
                    throw new Exception("Failed to check email existence");
                }
                
                $row = oci_fetch_assoc($stmt);
                oci_free_statement($stmt);
                
                return $row['COUNT'] > 0;
            } catch (Exception $e) {
                error_log("Error checking email existence: " . $e->getMessage());
                return false;
            }
        }
    }