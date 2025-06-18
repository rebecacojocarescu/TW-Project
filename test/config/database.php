<?php
    function getConnection(){
        // Enable error reporting
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        try {
            // Oracle database connection details
            $username = 'system';
            $password = 'STUDENT';
            $connection_string = '(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=localhost)(PORT=1522))(CONNECT_DATA=(SERVICE_NAME=XEPDB1)))';

            // Attempt to establish connection
            $conn = @oci_connect($username, $password, $connection_string);
            
            if (!$conn) {
                $e = oci_error();
                error_log("Database Connection Error: " . json_encode($e));
                throw new Exception("Could not connect to database. Please try again later.");
            }
            
            return $conn;
        } catch (Exception $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            throw new Exception("Could not connect to database. Please try again later.");
        }
    }
?>