<?php
function getConnection() {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    try {
        putenv("TNS_ADMIN=C:/xampp/htdocs/test/wallet");

        $username = 'ADMIN';
        $password = 'edisor12345678910A';
        $connection_string = 'pow_high';

        $conn = @oci_pconnect($username, $password, $connection_string);

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
