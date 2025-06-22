<?php
$base_path = dirname(dirname(__FILE__)) . '/';
require_once $base_path . 'models/ImageHandler.php';

error_log("PHP Upload Configuration: " . json_encode([
    'file_uploads' => ini_get('file_uploads'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_file_uploads' => ini_get('max_file_uploads'),
    'upload_tmp_dir' => ini_get('upload_tmp_dir'),
    'memory_limit' => ini_get('memory_limit')
]));

class Pet {
    private $conn;
    private $imageHandler;
    
    public function __construct($db) {
        $this->conn = $db;
        $this->imageHandler = new ImageHandler($db);
    }
    
    public function getPetById($id) {
        $query = "SELECT * FROM pets WHERE id = :pet_id";
        $stmt = oci_parse($this->conn, $query);
        
        if (!$stmt) {
            throw new Exception("Could not parse query");
        }
        
        oci_bind_by_name($stmt, ":pet_id", $id);
        $execute = oci_execute($stmt);
        
        if (!$execute) {
            throw new Exception("Could not execute query");
        }
        
        $pet = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);
        
        return $pet;
    }
    
    public function getPetMedia($pet_id) {
        $media_query = "SELECT * FROM media WHERE pet_id = :pet_id";
        $media_stmt = oci_parse($this->conn, $media_query);
        
        if (!$media_stmt) {
            throw new Exception("Could not parse media query");
        }
        
        oci_bind_by_name($media_stmt, ":pet_id", $pet_id);
        $media_execute = oci_execute($media_stmt);
        
        if (!$media_execute) {
            throw new Exception("Could not execute media query");
        }
        
        $media = array();
        while ($row = oci_fetch_assoc($media_stmt)) {
            $media[] = $row;
        }
        
        oci_free_statement($media_stmt);
        
        return $media;
    }    public function createPet($data) {
        try {            $query = "INSERT INTO pets (
                name, species, breed, age, gender, health_status, description,
                available_for_adoption, adoption_address, owner_id, personality_description,
                activity_description, diet_description, household_activity,
                household_environment, other_pets, color, marime, spayed_neutered,
                time_at_current_home, reason_for_rehoming, flea_treatment,
                current_owner_description, latitude, longitude
            ) VALUES (
                :name, :species, :breed, :age, :gender, :health_status, :description,
                1, :adoption_address, :owner_id, :personality_description,
                :activity_description, :diet_description, :household_activity,
                :household_environment, :other_pets, :color, :marime, :spayed_neutered,
                :time_at_current_home, :reason_for_rehoming, :flea_treatment,
                :current_owner_description, :latitude, :longitude
            ) RETURNING id INTO :inserted_id";

            $stmt = oci_parse($this->conn, $query);
            if (!$stmt) {
                $e = oci_error($this->conn);                throw new Exception("Could not parse create pet query: " . $e['message']);
            }

            // Set default values for any missing fields to prevent Oracle NULL constraint errors
            $requiredFields = [
                'health_status' => 'Good', 
                'description' => '', 
                'adoption_address' => '',
                'personality_description' => '', 
                'activity_description' => '',
                'diet_description' => '', 
                'household_activity' => '', 
                'household_environment' => '',
                'other_pets' => '', 
                'spayed_neutered' => 0, 
                'time_at_current_home' => '',
                'reason_for_rehoming' => '', 
                'flea_treatment' => '', 
                'current_owner_description' => ''
            ];

            foreach ($requiredFields as $field => $defaultValue) {
                if (!isset($data[$field]) || $data[$field] === '') {
                    $data[$field] = $defaultValue;
                }
            }

            // Log each field that will be bound
            foreach ($data as $key => $value) {
                if ($key !== 'pet_images') {                    oci_bind_by_name($stmt, ":{$key}", $data[$key]);
                }
            }            $inserted_id = 0;
            oci_bind_by_name($stmt, ":inserted_id", $inserted_id, -1, SQLT_INT);
            $execute = oci_execute($stmt);
            
            if (!$execute) {
                $e = oci_error($stmt);                throw new Exception("Could not create pet: " . $e['message']);
            }

            oci_free_statement($stmt);
              return $inserted_id;
        } catch (Exception $e) {
            throw $e;
        }
    }    private function beginTransaction() {
        $stmt = oci_parse($this->conn, "BEGIN NULL; END;");
        return oci_execute($stmt, OCI_NO_AUTO_COMMIT);
    }
    
    private function saveImages($petId, $images) {
        // Log PHP configuration
        $this->logUploadConfiguration();
        
        // Define directories - simplify paths to avoid permission issues
        $mainUploadDir = dirname(__DIR__) . '/uploads/';
        $petsDir = $mainUploadDir . 'pets/'; 
        $uploadDirectory = $petsDir . $petId . '/';
        $fallbackDirectory = $mainUploadDir;
        
        // Debug the upload directory
        
        // Check uploads directory permissions
        if (!file_exists($mainUploadDir)) {            if (!mkdir($mainUploadDir, 0777, true)) {                if (!mkdir($mainUploadDir, 0777, true)) {
                    // Use fallback to direct uploads folder
                    return $this->saveImagesToFallbackLocation($petId, $images);
                }
            }
        }
        
        // Ensure uploads directory is writable - this is critical
        if (!is_writable($mainUploadDir)) {            chmod($mainUploadDir, 0777);
            if (!is_writable($mainUploadDir)) {
                // Use fallback
                return $this->saveImagesToFallbackLocation($petId, $images);
            }
        }
        
        // Ensure pets directory exists
        if (!file_exists($petsDir)) {            if (!mkdir($petsDir, 0777, true)) {
                // Use fallback
                return $this->saveImagesToFallbackLocation($petId, $images);
            }
            chmod($petsDir, 0777); // Ensure it's writable
        }
        
        // Create pet-specific directory
        $useSubdirectory = true;
        if (!file_exists($uploadDirectory)) {            if (!mkdir($uploadDirectory, 0777, true)) {
                $useSubdirectory = false;
            } else {
                chmod($uploadDirectory, 0777); // Ensure it's writable
                if (!is_writable($uploadDirectory)) {
                    error_log("WARNING: New directory is not writable! Falling back.");
                    $useSubdirectory = false;
                }
            }
        }
        
        $uploadedFiles = [];
        $errorMessages = [];
          // Process each uploaded image
        for ($i = 0; $i < count($images['name']); $i++) {
            $currentFile = $images['name'][$i];
            $errorCode = $images['error'][$i];
            
            error_log("Processing file {$i}: {$currentFile} - Error code: {$errorCode}");
            
            if ($errorCode === 0) {
                // Generate a unique filename
                $fileExtension = pathinfo($currentFile, PATHINFO_EXTENSION);
                $fileName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '', basename($currentFile));
                
                // Determine the upload path
                if ($useSubdirectory) {
                    $targetPath = $uploadDirectory . $fileName;
                    $relativePath = 'uploads/pets/' . $petId . '/' . $fileName;
                } else {
                    // Use a fallback - put files directly in uploads with a pet ID prefix
                    $fileName = 'pet_' . $petId . '_' . $fileName;
                    $targetPath = $fallbackDirectory . $fileName;
                    $relativePath = 'uploads/' . $fileName;
                }
                
                $sourcePath = $images['tmp_name'][$i];
                
                error_log("Attempting to upload from {$sourcePath} to {$targetPath}");
                
                // Check if the source file exists and is a valid upload
                if (!is_uploaded_file($sourcePath)) {
                    $errorMessages[] = "Invalid upload or source file not found: {$sourcePath}";
                    error_log("ERROR: Not a valid uploaded file: {$sourcePath}");
                    continue;
                }
                  // Double-check that source exists
                if (!file_exists($sourcePath)) {
                    error_log("ERROR: Source file does not exist: {$sourcePath}");
                    $errorMessages[] = "Source file does not exist: {$sourcePath}";
                    continue;
                }
                
                // Make sure target directory exists
                $targetDir = dirname($targetPath);
                if (!file_exists($targetDir)) {
                    error_log("Creating target directory: {$targetDir}");
                    if (!mkdir($targetDir, 0777, true)) {
                        error_log("ERROR: Failed to create target directory: {$targetDir}");
                    }
                }
                
                if (move_uploaded_file($sourcePath, $targetPath)) {
                    error_log("Successfully moved file to {$targetPath}");
                    chmod($targetPath, 0644); // Ensure file is readable
                    
                    // Use the same relativePath that we determined above
                    error_log("Using relative path for database: {$relativePath}");
                    
                    try {
                        $query = "INSERT INTO media (pet_id, url, type) VALUES (:pet_id, :url, 'photo')";
                        $stmt = oci_parse($this->conn, $query);
                        
                        if (!$stmt) {
                            $error = oci_error($this->conn);
                            throw new Exception("Failed to parse statement: " . $error['message']);
                        }
                        
                        oci_bind_by_name($stmt, ":pet_id", $petId);
                        oci_bind_by_name($stmt, ":url", $relativePath);
                        
                        $success = oci_execute($stmt, OCI_NO_AUTO_COMMIT);
                        
                        if (!$success) {
                            $error = oci_error($stmt);
                            throw new Exception("Failed to execute statement: " . $error['message']);
                        }
                        
                        oci_free_statement($stmt);
                        
                        $uploadedFiles[] = $relativePath;
                        error_log("Added file record to database: {$relativePath}");
                    } catch (Exception $e) {
                        $errorMessages[] = "Database error: " . $e->getMessage();
                        error_log("Database error while saving image: " . $e->getMessage());
                        
                        // If we can't add to database, we should remove the file
                        if (file_exists($targetPath)) {
                            unlink($targetPath);
                        }
                    }
                } else {
                    $errorMessages[] = "Failed to move uploaded file to {$targetPath}";
                    error_log("Failed to move uploaded file to {$targetPath}");
                }
            } else {
                $errorMessages[] = "Upload error for file {$currentFile}: Error code {$errorCode}";
                error_log("Upload error for file {$currentFile}: Error code {$errorCode}");
            }
        }
        
        if (!empty($errorMessages)) {
            error_log("Image upload errors: " . implode(", ", $errorMessages));
        }
        
        return $uploadedFiles;
    }    private function saveImagesToFallbackLocation($petId, $images) {
        $mainUploadDir = dirname(__DIR__) . '/uploads/';
        $uploadedFiles = [];
        $errorMessages = [];
        
        error_log("Using fallback location for images: " . $mainUploadDir);
        
        // Process each uploaded image
        for ($i = 0; $i < count($images['name']); $i++) {
            $currentFile = $images['name'][$i];
            $errorCode = $images['error'][$i];
            
            error_log("Processing file {$i}: {$currentFile} - Error code: {$errorCode}");
            
            if ($errorCode === 0) {
                // Generate a unique filename with pet ID prefix
                $fileExtension = pathinfo($currentFile, PATHINFO_EXTENSION);
                $fileName = 'pet_' . $petId . '_' . uniqid() . '.' . $fileExtension;
                
                // Direct path in uploads directory
                $targetPath = $mainUploadDir . $fileName;
                $relativePath = 'uploads/' . $fileName;
                
                $sourcePath = $images['tmp_name'][$i];
                
                error_log("Attempting to upload from {$sourcePath} to {$targetPath}");
                
                // Check if the source file exists and is a valid upload
                if (!is_uploaded_file($sourcePath)) {
                    $errorMessages[] = "Invalid upload or source file not found: {$sourcePath}";
                    continue;
                }
                
                if (move_uploaded_file($sourcePath, $targetPath)) {
                    error_log("Successfully moved file to {$targetPath}");
                    chmod($targetPath, 0644); // Ensure file is readable
                    
                    // Save in media table
                    try {
                        $query = "INSERT INTO media (pet_id, url, type) VALUES (:pet_id, :url, 'photo')";
                        $stmt = oci_parse($this->conn, $query);
                        
                        if (!$stmt) {
                            $error = oci_error($this->conn);
                            throw new Exception("Failed to parse statement: " . $error['message']);
                        }
                        
                        oci_bind_by_name($stmt, ":pet_id", $petId);
                        oci_bind_by_name($stmt, ":url", $relativePath);
                        
                        $success = oci_execute($stmt, OCI_NO_AUTO_COMMIT);
                        
                        if (!$success) {
                            $error = oci_error($stmt);
                            throw new Exception("Failed to execute statement: " . $error['message']);
                        }
                        
                        oci_free_statement($stmt);
                        
                        $uploadedFiles[] = $relativePath;
                        error_log("Added file record to database: {$relativePath}");
                    } catch (Exception $e) {
                        $errorMessages[] = "Database error: " . $e->getMessage();
                        error_log("Database error while saving image: " . $e->getMessage());
                        
                        // If we can't add to database, remove the file
                        if (file_exists($targetPath)) {
                            unlink($targetPath);
                        }
                    }
                } else {
                    $error = error_get_last();
                    $errorMessages[] = "Failed to move uploaded file to {$targetPath}. Error: " . ($error ? $error['message'] : 'Unknown');
                    error_log("Failed to move uploaded file to {$targetPath}. Error: " . ($error ? $error['message'] : 'Unknown'));
                }
            } else {
                $errorMessages[] = "Upload error for file {$currentFile}: Error code {$errorCode}";
                error_log("Upload error for file {$currentFile}: Error code {$errorCode}");
            }
        }
        
        if (!empty($errorMessages)) {
            error_log("Image upload errors: " . implode(", ", $errorMessages));
        }
        
        return $uploadedFiles;
    }

    private function getCoordinatesFromAddress($address) {
        $apiKey = 'AIzaSyC9pBmG3InVXEsgC5Hee4KPpU8n87dNNzQ';
        
        // Adăugăm "Romania" la adresă dacă nu este deja specificată
        if (stripos($address, 'romania') === false && stripos($address, 'românia') === false) {
            $address .= ', Romania';
        }
        
        // Înlocuim caracterele speciale românești cu echivalentele lor
        $address = str_replace(
            ['ă', 'â', 'î', 'ș', 'ț', 'Ă', 'Â', 'Î', 'Ș', 'Ț'],
            ['a', 'a', 'i', 's', 't', 'A', 'A', 'I', 'S', 'T'],
            $address
        );
        
        $address = urlencode($address);
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$address}&key={$apiKey}&region=ro";
        
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        
        if ($data['status'] === 'OK') {
            // Verificăm dacă rezultatul este în România
            $isRomania = false;
            foreach ($data['results'][0]['address_components'] as $component) {
                if (in_array('country', $component['types']) && $component['short_name'] === 'RO') {
                    $isRomania = true;
                    break;
                }
            }
            
            if ($isRomania) {
                return [
                    'lat' => $data['results'][0]['geometry']['location']['lat'],
                    'lng' => $data['results'][0]['geometry']['location']['lng']
                ];
            }
        }
        
        // Dacă nu am găsit coordonatele pentru Iași, folosim coordonatele default pentru Iași
        return [
            'lat' => 47.1585,
            'lng' => 27.6014
        ];
    }

    public function getAllPetsWithCoordinates() {
        $query = "SELECT id, name, species, latitude, longitude FROM pets WHERE latitude IS NOT NULL AND longitude IS NOT NULL";
        $stmt = oci_parse($this->conn, $query);
        
        if (!$stmt) {
            throw new Exception("Could not parse query for pet coordinates");
        }
        
        $execute = oci_execute($stmt);
        
        if (!$execute) {
            $e = oci_error($stmt);
            throw new Exception("Could not execute pet coordinates query: " . $e['message']);
        }
        
        $pets = array();
        while ($row = oci_fetch_assoc($stmt)) {
            $pets[] = $row;
        }
        
        oci_free_statement($stmt);
        
        return $pets;
    }

    public function uploadPetImages($pet_id, $files) {
        return $this->imageHandler->uploadPetImages($pet_id, $files);
    }

    public function getPetsByUserId($userId) {
        $query = "SELECT * FROM pets WHERE owner_id = :user_id ORDER BY id DESC";
        $stmt = oci_parse($this->conn, $query);
        
        if (!$stmt) {
            throw new Exception("Could not parse query");
        }
        
        oci_bind_by_name($stmt, ":user_id", $userId);
        $execute = oci_execute($stmt);
        
        if (!$execute) {
            throw new Exception("Could not execute query");
        }
        
        $pets = array();
        while ($row = oci_fetch_assoc($stmt)) {
            $pets[] = $row;
        }
        
        oci_free_statement($stmt);
        
        return $pets;
    }    public function getUserPets($userId) {
        try {
            if (!$userId) {
                throw new Exception("Invalid user ID provided");
            }

            // Query pets with essential fields
            $query = "SELECT 
                        p.id, p.name, p.species, p.breed, p.age, 
                        p.gender, p.health_status, p.description,
                        p.available_for_adoption, p.adoption_address,
                        p.owner_id, p.color, p.marime,
                        p.spayed_neutered
                     FROM pets p 
                     WHERE p.owner_id = :user_id 
                     ORDER BY p.id DESC";
                     
            $stmt = oci_parse($this->conn, $query);
            
            if (!$stmt) {
                $error = oci_error($this->conn);
                error_log("Parse error in getUserPets: " . print_r($error, true));
                throw new Exception("Database error while preparing query");
            }
            
            // Bind user ID as number to ensure proper comparison
            $userIdNum = (int)$userId;
            oci_bind_by_name($stmt, ":user_id", $userIdNum);
            
            $execute = oci_execute($stmt);
            
            if (!$execute) {
                $error = oci_error($stmt);
                error_log("Execute error in getUserPets: " . print_r($error, true));
                throw new Exception("Database error while fetching pets");
            }
            
            $pets = [];
            while ($row = oci_fetch_assoc($stmt)) {
                // Convert numeric strings to actual numbers
                $row['ID'] = (int)$row['ID'];
                $row['AGE'] = is_numeric($row['AGE']) ? (int)$row['AGE'] : null;
                $row['OWNER_ID'] = (int)$row['OWNER_ID'];
                $row['SPAYED_NEUTERED'] = (bool)$row['SPAYED_NEUTERED'];
                $row['AVAILABLE_FOR_ADOPTION'] = (bool)$row['AVAILABLE_FOR_ADOPTION'];
                
                $pets[] = $row;
            }
            
            oci_free_statement($stmt);
            
            return $pets;
            
        } catch (Exception $e) {
            error_log("Error in getUserPets model: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function deletePet($petId) {
        try {
            // Delete related records from all dependent tables in the correct order
            // Tables with no dependencies from other tables go first
            $dependentTables = [
                'messages',           // Messages referencing the pet
                'media',             // Media files
                'feeding_schedule',   // Feeding schedule
                'restrictions',       // Restrictions
                'medical_history',    // Medical history
                'rss_feed',          // RSS feed entries
                'adoption_form',      // Adoption forms
                'adoptions'          // Adoption records (must be after adoption_form)
            ];

            foreach ($dependentTables as $table) {
                $query = "DELETE FROM {$table} WHERE pet_id = :pet_id";
                $stmt = oci_parse($this->conn, $query);
                
                if (!$stmt) {
                    throw new Exception("Could not parse delete query for {$table}");
                }
                
                oci_bind_by_name($stmt, ":pet_id", $petId);
                // Use OCI_NO_AUTO_COMMIT flag to handle transaction manually
                $execute = oci_execute($stmt, OCI_NO_AUTO_COMMIT);
                
                if (!$execute) {
                    $e = oci_error($stmt);
                    oci_rollback($this->conn);
                    throw new Exception("Could not delete from {$table}: " . $e['message']);
                }
                
                oci_free_statement($stmt);
            }

            // Finally, delete the pet itself
            $query = "DELETE FROM pets WHERE id = :pet_id";
            $stmt = oci_parse($this->conn, $query);
            
            if (!$stmt) {
                oci_rollback($this->conn);
                throw new Exception("Could not parse delete pet query");
            }
            
            oci_bind_by_name($stmt, ":pet_id", $petId);
            // Use OCI_NO_AUTO_COMMIT flag to handle transaction manually
            $execute = oci_execute($stmt, OCI_NO_AUTO_COMMIT);
            
            if (!$execute) {
                $e = oci_error($stmt);
                oci_rollback($this->conn);
                throw new Exception("Could not delete pet: " . $e['message']);
            }
            
            oci_free_statement($stmt);

            // Commit all changes
            $commit = oci_commit($this->conn);
            if (!$commit) {
                oci_rollback($this->conn);
                throw new Exception("Could not commit transaction");
            }
            
            return true;
        } catch (Exception $e) {
            // Rollback is already handled in the specific error cases
            throw $e;
        }
    }

    private function deleteAllPetMedia($petId) {
        // Mai întâi obținem toate imaginile
        $media = $this->getPetMedia($petId);
        
        // Ștergem fișierele fizice
        foreach ($media as $item) {
            if (isset($item['URL']) && !empty($item['URL'])) {
                $filePath = dirname(dirname(__FILE__)) . '/' . $item['URL'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
        }

        // Ștergem înregistrările din baza de date
        $query = "DELETE FROM media WHERE pet_id = :pet_id";
        $stmt = oci_parse($this->conn, $query);
        
        if (!$stmt) {
            throw new Exception("Could not parse delete media query");
        }
        
        oci_bind_by_name($stmt, ":pet_id", $petId);
        $execute = oci_execute($stmt);
        
        if (!$execute) {
            throw new Exception("Could not delete pet media");
        }
        
        oci_free_statement($stmt);
        return true;
    }
      private function getUploadErrorMessage($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_OK:
                return "No error, file upload successful";
            case UPLOAD_ERR_INI_SIZE:
                return "File exceeds the upload_max_filesize directive in php.ini";
            case UPLOAD_ERR_FORM_SIZE:
                return "File exceeds the MAX_FILE_SIZE directive in the HTML form";
            case UPLOAD_ERR_PARTIAL:
                return "File was only partially uploaded";
            case UPLOAD_ERR_NO_FILE:
                return "No file was uploaded";
            case UPLOAD_ERR_NO_TMP_DIR:
                return "Missing a temporary folder";
            case UPLOAD_ERR_CANT_WRITE:
                return "Failed to write file to disk";
            case UPLOAD_ERR_EXTENSION:
                return "A PHP extension stopped the file upload";
            default:
                return "Unknown upload error";
        }
    }
      private function logUploadConfiguration() {
        $uploadConfig = array(
            'file_uploads' => ini_get('file_uploads'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_file_uploads' => ini_get('max_file_uploads'),
            'upload_tmp_dir' => ini_get('upload_tmp_dir'),
            'memory_limit' => ini_get('memory_limit')
        );
        
        error_log("PHP Upload Configuration: " . json_encode($uploadConfig));
    }
}