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
                $sql = "SELECT password FROM users WHERE name = :name AND surname = :surname";
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
                
                return $verified;
            } catch (Exception $e) {
                error_log("Error during authentication: " . $e->getMessage());
                return false;
            }
        }
    }