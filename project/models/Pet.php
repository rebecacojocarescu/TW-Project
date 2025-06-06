<?php
require_once '../model/ImageHandler.php';

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
            current_owner_description
        ) VALUES (
            :name, :species, :breed, :age, :gender, :health_status, :description,
            1, :adoption_address, :owner_id, :personality_description,
            :activity_description, :diet_description, :household_activity,
            :household_environment, :other_pets, :color, :marime, :spayed_neutered,
            :time_at_current_home, :reason_for_rehoming, :flea_treatment,
            :current_owner_description
        ) RETURNING id INTO :inserted_id";

        $stmt = oci_parse($this->conn, $query);
        if (!$stmt) {
            throw new Exception("Could not parse create pet query");
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

    public function uploadPetImages($pet_id, $files) {
        return $this->imageHandler->uploadPetImages($pet_id, $files);
    }
} 