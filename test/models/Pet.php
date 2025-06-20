<?php
require_once '../models/ImageHandler.php';

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
    }

    public function createPet($data) {
        $query = "INSERT INTO pets (
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
            throw new Exception("Could not parse create pet query");
        }

        // Folosim coordonatele din formular dacă există, altfel le obținem din adresă
        if (!isset($data['latitude']) || !isset($data['longitude'])) {
            $coordinates = $this->getCoordinatesFromAddress($data['adoption_address']);
            $data['latitude'] = $coordinates['lat'];
            $data['longitude'] = $coordinates['lng'];
        }

        foreach ($data as $key => $value) {
            if ($key !== 'pet_images') { // Excludem imaginile
                oci_bind_by_name($stmt, ":{$key}", $data[$key]);
            }
        }

        $inserted_id = 0;
        oci_bind_by_name($stmt, ":inserted_id", $inserted_id, -1, SQLT_INT);

        $execute = oci_execute($stmt);
        if (!$execute) {
            $e = oci_error($stmt);
            throw new Exception("Could not create pet: " . $e['message']);
        }

        oci_free_statement($stmt);
        return $inserted_id;
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
        $query = "SELECT id, name, species, latitude, longitude FROM pets WHERE available_for_adoption = 1";
        $stmt = oci_parse($this->conn, $query);
        
        if (!$stmt) {
            throw new Exception("Could not parse query");
        }
        
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
} 