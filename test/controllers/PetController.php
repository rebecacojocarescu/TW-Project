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
            // Validare ID
            $pet_id = (int)$id;
            if ($pet_id <= 0) {
                throw new Exception("Invalid pet ID");
            }
            
            // Obține informațiile despre animal
            $pet = $this->petModel->getPetById($pet_id);
            if (!$pet) {
                throw new Exception("Pet not found");
            }
            
            // Obține media asociată
            $media = $this->petModel->getPetMedia($pet_id);
            
            // Încarcă view-ul
            require_once '../views/pets/show.php';
            
        } catch (Exception $e) {
            // În caz de eroare, redirecționează către pagina de listă
            header("Location: lista-animale.php");
            exit;
        }
    }
} 