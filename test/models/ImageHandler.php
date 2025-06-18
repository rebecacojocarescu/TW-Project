<?php
class ImageHandler {
    private $conn;
    private $uploadPath = '../uploads/';
    private $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    private $maxFileSize = 5242880; // 5MB
    
    public function __construct($db) {
        $this->conn = $db;
        
        if (!file_exists($this->uploadPath)) {
            mkdir($this->uploadPath, 0777, true);
        }
    }
    
    public function uploadPetImages($pet_id, $files) {
        $uploadedFiles = [];
        $errors = [];
        
        // Verificăm dacă există fișiere
        if (!isset($files['name']) || empty($files['name'][0])) {
            return ['success' => false, 'errors' => ['No files uploaded']];
        }
        
        // Procesăm fiecare fișier
        for ($i = 0; $i < count($files['name']); $i++) {
            $fileName = $files['name'][$i];
            $fileType = $files['type'][$i];
            $fileTmpName = $files['tmp_name'][$i];
            $fileError = $files['error'][$i];
            $fileSize = $files['size'][$i];
            
            // Verificări de bază
            if ($fileError !== UPLOAD_ERR_OK) {
                $errors[] = "Upload error for file: $fileName";
                continue;
            }
            
            if (!in_array($fileType, $this->allowedTypes)) {
                $errors[] = "Invalid file type for: $fileName";
                continue;
            }
            
            if ($fileSize > $this->maxFileSize) {
                $errors[] = "File too large: $fileName";
                continue;
            }
            
            // Generăm un nume unic pentru fișier
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
            $newFileName = uniqid('pet_' . $pet_id . '_') . '.' . $fileExtension;
            $targetPath = $this->uploadPath . $newFileName;
            
            // Încercăm să mutăm fișierul
            if (move_uploaded_file($fileTmpName, $targetPath)) {
                // Salvăm informațiile în baza de date
                $relativePath = 'uploads/' . $newFileName;
                $mediaType = 'photo'; // Setăm tipul corect pentru baza de date
                
                $query = "INSERT INTO media (pet_id, url, type) VALUES (:pet_id, :url, :type)";
                $stmt = oci_parse($this->conn, $query);
                
                if ($stmt) {
                    oci_bind_by_name($stmt, ":pet_id", $pet_id);
                    oci_bind_by_name($stmt, ":url", $relativePath);
                    oci_bind_by_name($stmt, ":type", $mediaType);
                    
                    if (oci_execute($stmt)) {
                        $uploadedFiles[] = $relativePath;
                    } else {
                        $e = oci_error($stmt);
                        $errors[] = "Database error for file: $fileName - " . $e['message'];
                        // În caz de eroare, ștergem fișierul uploadat
                        unlink($targetPath);
                    }
                    
                    oci_free_statement($stmt);
                } else {
                    $errors[] = "Database error for file: $fileName";
                    unlink($targetPath);
                }
            } else {
                $errors[] = "Could not move file: $fileName";
            }
        }
        
        return [
            'success' => !empty($uploadedFiles),
            'files' => $uploadedFiles,
            'errors' => $errors
        ];
    }
    
    public function deleteImage($media_id) {
        // Mai întâi obținem URL-ul fișierului
        $query = "SELECT url FROM media WHERE id = :media_id";
        $stmt = oci_parse($this->conn, $query);
        
        if (!$stmt) {
            return false;
        }
        
        oci_bind_by_name($stmt, ":media_id", $media_id);
        
        if (!oci_execute($stmt)) {
            oci_free_statement($stmt);
            return false;
        }
        
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);
        
        if (!$row) {
            return false;
        }
        
        $filePath = '../' . $row['URL'];
        
        // Ștergem înregistrarea din baza de date
        $query = "DELETE FROM media WHERE id = :media_id";
        $stmt = oci_parse($this->conn, $query);
        
        if (!$stmt) {
            return false;
        }
        
        oci_bind_by_name($stmt, ":media_id", $media_id);
        $result = oci_execute($stmt);
        oci_free_statement($stmt);
        
        if ($result) {
            // Dacă ștergerea din baza de date a reușit, încercăm să ștergem și fișierul fizic
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            return true;
        }
        
        return false;
    }
    
    public function getImagesByPetId($pet_id) {
        $query = "SELECT * FROM media WHERE pet_id = :pet_id";
        $stmt = oci_parse($this->conn, $query);
        
        if (!$stmt) {
            return [];
        }
        
        oci_bind_by_name($stmt, ":pet_id", $pet_id);
        
        if (!oci_execute($stmt)) {
            oci_free_statement($stmt);
            return [];
        }
        
        $images = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $images[] = $row;
        }
        
        oci_free_statement($stmt);
        return $images;
    }
} 