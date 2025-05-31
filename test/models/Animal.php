<?php
class Animal {
    public static function getAnimalsByType($type) {
        $conn = getConnection();
        
        // Create the PL/SQL function call
        $sql = "BEGIN
                    :result := get_animals_by_type(:animal_type);
                END;";
                
        $stmt = oci_parse($conn, $sql);
        
        // Create a ref cursor for the result
        $result = oci_new_cursor($conn);
        
        // Bind the parameters
        oci_bind_by_name($stmt, ":result", $result, -1, SQLT_RSET);
        oci_bind_by_name($stmt, ":animal_type", $type);
        
        // Execute the statement
        oci_execute($stmt);
        oci_execute($result);
        
        $animals = array();
        
        // Fetch all columns from pets table
        while ($row = oci_fetch_assoc($result)) {
            // Convert all keys to lowercase for consistency
            $animal = array();
            foreach ($row as $key => $value) {
                $animal[strtolower($key)] = $value;
            }
            $animals[] = $animal;
        }
        
        oci_free_statement($stmt);
        oci_free_statement($result);
        oci_close($conn);
        
        return $animals;
    }
}
?> 