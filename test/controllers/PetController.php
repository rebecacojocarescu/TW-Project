<?php
require_once '../models/Pet.php';
require_once '../config/database.php';

class PetController {
    private $petModel;
    private $db;
    
    public function __construct() {
        $this->db = getConnection();
        $this->petModel = new Pet($this->db);
    }
    
    public function __destruct() {
        if ($this->db) {
            oci_close($this->db);
        }
    }
    
    public function showPetDetails($id) {
        try {
            $pet_id = (int)$id;
            if ($pet_id <= 0) {
                return ['error' => "Invalid pet ID"];
            }
            
            $pet = $this->petModel->getPetById($pet_id);
            if (!$pet) {
                return ['error' => "Pet not found"];
            }
            
            $media = $this->petModel->getPetMedia($pet_id);
            
            return [
                'pet' => $pet,
                'media' => $media
            ];
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function getAllPetsWithCoordinates() {
        try {
            return $this->petModel->getAllPetsWithCoordinates();
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function createPet($data, $files = null) {
        try {
            $requiredFields = [
                'name', 'species', 'breed', 'age', 'gender', 'health_status',
                'personality_description', 'activity_description', 'diet_description',
                'household_activity', 'household_environment', 'other_pets',
                'color', 'marime', 'time_at_current_home', 'reason_for_rehoming',
                'current_owner_description', 'adoption_address'
            ];

            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty(trim($data[$field]))) {
                    return ['error' => "Field {$field} is required"];
                }
            }

            $data['spayed_neutered'] = isset($data['spayed_neutered']) ? 1 : 0;
            $data['flea_treatment'] = isset($data['flea_treatment']) ? 1 : 0;

            $pet_id = $this->petModel->createPet($data);
            if (!$pet_id) {
                return ['error' => "Failed to create pet"];
            }

            if ($files && isset($files['pet_images']) && !empty($files['pet_images']['name'][0])) {
                $uploadResult = $this->petModel->uploadPetImages($pet_id, $files['pet_images']);
                if (!$uploadResult['success']) {
                    error_log("Image upload errors for pet {$pet_id}: " . implode(", ", $uploadResult['errors']));
                }
            }

            return [
                'success' => true,
                'pet_id' => $pet_id
            ];

        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
} 