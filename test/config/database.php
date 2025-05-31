<?php
    function getConnection(){
        // Enable error reporting
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        // Oracle database connection details
        $username = 'system';
        $password = 'STUDENT';
        $connection_string = '(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=localhost)(PORT=1522))(CONNECT_DATA=(SERVICE_NAME=XEPDB1)))';

        // Attempt to establish connection
        $conn = @oci_connect($username, $password, $connection_string);
        
        if (!$conn) {
            $e = oci_error();
            echo "Database Connection Error:<br>";
            echo "Message: " . $e['message'] . "<br>";
            echo "Code: " . $e['code'] . "<br>";
            echo "File: " . $e['file'] . "<br>";
            echo "Line: " . $e['line'] . "<br>";
            die();
        }
        
        return $conn;
    }
?>