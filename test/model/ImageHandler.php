<?php
class ImageHandler {
    private $uploadDir;
    private $conn;
    
    public function __construct($conn) {
        $this->uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'pets' . DIRECTORY_SEPARATOR;
        $this->conn = $conn;
    }
    
    private function checkPetExists($petId) {
        $query = "SELECT id FROM pets WHERE id = :pet_id";
        $stmt = oci_parse($this->conn, $query);
        if (!$stmt) {
            return false;
        }
        
        oci_bind_by_name($stmt, ":pet_id", $petId);
        if (!oci_execute($stmt)) {
            return false;
        }
        
        $exists = (oci_fetch_assoc($stmt) !== false);
        oci_free_statement($stmt);
        return $exists;
    }
    
    public function uploadPetImages($petId, $files) {
        $uploadedFiles = [];
        $errors = [];
        
        // First check if the pet exists
        if (!$this->checkPetExists($petId)) {
            return [
                'success' => false,
                'files' => [],
                'errors' => ["Pet with ID {$petId} does not exist in the database"]
            ];
        }
        
        // Create pet-specific directory if it doesn't exist
        $petDir = $this->uploadDir . $petId . DIRECTORY_SEPARATOR;
        if (!file_exists($petDir)) {
            if (!mkdir($petDir, 0777, true)) {
                return [
                    'success' => false,
                    'files' => [],
                    'errors' => ["Failed to create directory for pet ID {$petId}"]
                ];
            }
        }
        
        // Process each image
        foreach ($files['tmp_name'] as $key => $tmp_name) {
            if ($files['error'][$key] === 0) {
                $fileName = $files['name'][$key];
                $fileType = $files['type'][$key];
                
                // Verify if it's an image
                if (!$this->isImage($fileType)) {
                    $errors[] = "File {$fileName} is not a valid image.";
                    continue;
                }
                
                // Generate unique filename
                $newFileName = time() . '_' . uniqid() . '_' . $this->sanitizeFileName($fileName);
                $filePath = $petDir . $newFileName;
                
                // Try to move the file
                if (move_uploaded_file($tmp_name, $filePath)) {
                    // Save to database with forward slashes for URLs
                    $relativeUrl = str_replace(DIRECTORY_SEPARATOR, '/', 'uploads/pets/' . $petId . '/' . $newFileName);
                    try {
                        if ($this->saveToDatabase($petId, $relativeUrl)) {
                            $uploadedFiles[] = $relativeUrl;
                        } else {
                            $error = $this->getLastDatabaseError();
                            $errors[] = "Error saving to database for {$fileName}: " . $error;
                            // Delete file if database save failed
                            if (file_exists($filePath)) {
                                unlink($filePath);
                            }
                        }
                    } catch (Exception $e) {
                        $errors[] = "Database error for {$fileName}: " . $e->getMessage();
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                    }
                } else {
                    $errors[] = "Error uploading file {$fileName}";
                }
            } else {
                $errors[] = "Upload error: " . $this->getUploadError($files['error'][$key]);
            }
        }
        
        return [
            'success' => !empty($uploadedFiles),
            'files' => $uploadedFiles,
            'errors' => $errors
        ];
    }
    
    private function getLastDatabaseError() {
        $error = oci_error($this->conn);
        return $error ? $error['message'] : "Unknown database error";
    }
    
    private function isImage($fileType) {
        $allowedTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp'
        ];
        return in_array($fileType, $allowedTypes);
    }
    
    private function saveToDatabase($petId, $url) {
        try {
            // First check if we already have this URL in the database
            $check_query = "SELECT COUNT(*) as count FROM media WHERE pet_id = :pet_id AND url = :url";
            $check_stmt = oci_parse($this->conn, $check_query);
            
            oci_bind_by_name($check_stmt, ":pet_id", $petId);
            oci_bind_by_name($check_stmt, ":url", $url);
            
            if (!oci_execute($check_stmt)) {
                $error = oci_error($check_stmt);
                throw new Exception($error['message']);
            }
            
            $row = oci_fetch_assoc($check_stmt);
            if ($row && $row['COUNT'] > 0) {
                oci_free_statement($check_stmt);
                throw new Exception("This image has already been uploaded for this pet");
            }
            
            oci_free_statement($check_stmt);
            
            // If we get here, we can insert the new record
            $query = "INSERT INTO media (pet_id, type, url, upload_date) 
                     VALUES (:pet_id, 'photo', :url, CURRENT_TIMESTAMP)";
            $stmt = oci_parse($this->conn, $query);
            
            oci_bind_by_name($stmt, ":pet_id", $petId);
            oci_bind_by_name($stmt, ":url", $url);
            
            $result = oci_execute($stmt);
            oci_free_statement($stmt);
            
            return $result;
        } catch (Exception $e) {
            error_log("Error saving image to database: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function getPetImages($petId) {
        try {
            $query = "SELECT id, url, upload_date 
                     FROM media 
                     WHERE pet_id = :pet_id AND type = 'image' 
                     ORDER BY upload_date DESC";
            $stmt = oci_parse($this->conn, $query);
            
            oci_bind_by_name($stmt, ":pet_id", $petId);
            oci_execute($stmt);
            
            $images = [];
            while ($row = oci_fetch_assoc($stmt)) {
                $images[] = $row;
            }
            
            return $images;
        } catch (Exception $e) {
            error_log("Error fetching pet images: " . $e->getMessage());
            return [];
        }
    }
    
    public function deleteImage($imageId) {
        try {
            // First get the image URL
            $query = "SELECT url FROM media WHERE id = :id";
            $stmt = oci_parse($this->conn, $query);
            oci_bind_by_name($stmt, ":id", $imageId);
            oci_execute($stmt);
            
            $row = oci_fetch_assoc($stmt);
            if (!$row) {
                return false;
            }
            
            $filePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $row['URL']);
            
            // Delete from database
            $query = "DELETE FROM media WHERE id = :id";
            $stmt = oci_parse($this->conn, $query);
            oci_bind_by_name($stmt, ":id", $imageId);
            
            if (oci_execute($stmt)) {
                // If deleted from database, delete the physical file
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error deleting image: " . $e->getMessage());
            return false;
        }
    }
    
    private function getUploadError($code) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => "Fișierul depășește limita de mărime setată în php.ini",
            UPLOAD_ERR_FORM_SIZE => "Fișierul depășește limita de mărime setată în formularul HTML",
            UPLOAD_ERR_PARTIAL => "Fișierul a fost încărcat doar parțial",
            UPLOAD_ERR_NO_FILE => "Nu a fost încărcat niciun fișier",
            UPLOAD_ERR_NO_TMP_DIR => "Lipsește un director temporar",
            UPLOAD_ERR_CANT_WRITE => "Nu s-a putut scrie fișierul pe disc",
            UPLOAD_ERR_EXTENSION => "O extensie PHP a oprit încărcarea fișierului"
        ];
        return isset($errors[$code]) ? $errors[$code] : "Eroare necunoscută";
    }
    
    private function sanitizeFileName($fileName) {
        // Remove any character that isn't a word character, dash, or dot
        $fileName = preg_replace('/[^\w\-\.]/', '', $fileName);
        // Ensure the filename is unique by adding a timestamp if needed
        return $fileName;
    }
} 