<?php

ob_start();

require_once dirname(dirname(__FILE__)) . '/config/database.php';
require_once dirname(dirname(__FILE__)) . '/models/Pet.php';
require_once dirname(dirname(__FILE__)) . '/utils/auth_middleware.php';

class PetController {
    private $conn;
    private $petModel;
    
   
    public function __construct() {
        try {
            $this->conn = getConnection();
            $this->petModel = new Pet($this->conn);
        } catch (Exception $e) {
            error_log("Database connection error in PetController: " . $e->getMessage());
        }
    }
    

    public function __destruct() {
        if ($this->conn) {
            oci_close($this->conn);
        }
    }
    
    /**
     * Handle direct script access for web requests
     */
    public static function handleRequest() {
        session_start();
        
        // Check if user is authenticated
        if (!isset($_SESSION['user_id'])) {
            self::sendJsonResponse(false, 'Not authenticated');
        }
        
        $controller = new self();
        
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
                switch ($_POST['action']) {
                    case 'delete':
                        $controller->deletePetPost();
                        break;
                    default:
                        throw new Exception('Invalid action');
                }
            } else {
                throw new Exception('Invalid request method or missing action');
            }
        } catch (Exception $e) {
            error_log("Error in PetController: " . $e->getMessage());
            self::sendJsonResponse(false, $e->getMessage());
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
    

    public function createPetData($data, $files = null) {
        try {
            $requiredFields = [
                'name', 'species', 'breed', 'age', 'gender', 'health_status',
                'personality_description', 'activity_description', 'diet_description',
                'household_activity', 'household_environment', 'other_pets',
                'color', 'marime', 'time_at_current_home', 'reason_for_rehoming',
                'current_owner_description', 'adoption_address', 'latitude', 'longitude'
            ];

            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || (is_string($data[$field]) && empty(trim($data[$field]))) || 
                    (in_array($field, ['latitude', 'longitude']) && !is_numeric($data[$field]))) {
                    return ['error' => "Field {$field} is required and must be valid"];
                }
            }

            $data['spayed_neutered'] = isset($data['spayed_neutered']) ? 1 : 0;
            $data['flea_treatment'] = isset($data['flea_treatment']) ? 1 : 0;

            $data['latitude'] = floatval($data['latitude']);
            $data['longitude'] = floatval($data['longitude']);

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
            error_log("Exception in createPet: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }


    public function getUserPetsData($userId) {
        try {
            return $this->petModel->getPetsByUserId($userId);
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }


    public function deletePetPost() {
        try {
            if (!isset($_POST['pet_id'])) {
                throw new Exception("Pet ID is required");
            }

            $pet_id = (int)$_POST['pet_id'];
            if ($pet_id <= 0) {
                throw new Exception("Invalid pet ID");
            }

            $user_id = $_SESSION['user_id'];
            if (!$user_id) {
                throw new Exception("User not authenticated");
            }

            $pet = $this->petModel->getPetById($pet_id);
            if (!$pet) {
                throw new Exception("Pet not found");
            }

            if ($pet['OWNER_ID'] != $user_id) {
                throw new Exception("You don't have permission to delete this pet");
            }

            $result = $this->petModel->deletePet($pet_id);
            
            if ($result) {
                self::sendJsonResponse(true);
            } else {
                self::sendJsonResponse(false, 'Failed to delete pet');
            }

        } catch (Exception $e) {
            error_log("Error deleting pet: " . $e->getMessage());
            self::sendJsonResponse(false, $e->getMessage());
        }
    }


    public function deletePetData($petId) {
        try {
            $pet_id = (int)$petId;
            if ($pet_id <= 0) {
                return ['error' => "Invalid pet ID"];
            }
            
            $user_id = $_SESSION['user_id'] ?? null;
            if (!$user_id) {
                return ['error' => "User not authenticated"];
            }
            
            $pet = $this->petModel->getPetById($pet_id);
            if (!$pet) {
                return ['error' => "Pet not found"];
            }
            
            if ($pet['OWNER_ID'] != $user_id) {
                return ['error' => "You don't have permission to delete this pet"];
            }
            
            $result = $this->petModel->deletePet($pet_id);
            
            if ($result) {
                return ['success' => true];
            } else {
                return ['error' => "Failed to delete pet"];
            }
            
        } catch (Exception $e) {
            error_log("Error in deletePet: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }


    public function getDefaultPetImage($petId, $species) {
        try {
            $media = $this->petModel->getPetMedia($petId);
            if (!empty($media) && isset($media[0]['URL']) && !empty($media[0]['URL'])) {
                return '../' . $media[0]['URL'];
            }
            
            $species = strtolower($species);
            switch ($species) {
                case 'câine':
                case 'caine':
                case 'dog':
                    return '../stiluri/imagini/dog.png';
                case 'pisică':
                case 'pisica':
                case 'cat':
                    return '../stiluri/imagini/cat.png';
                case 'pasăre':
                case 'pasare':
                case 'bird':
                    return '../stiluri/imagini/bird.png';
                case 'pește':
                case 'peste':
                case 'fish':
                    return '../stiluri/imagini/fish.png';
                default:
                    return '../stiluri/imagini/dog.png';
            }
        } catch (Exception $e) {
            error_log("Error getting default pet image: " . $e->getMessage());
            return '../stiluri/imagini/dog.png';
        }
    }


    public static function sendJsonResponse($success, $message = null, $data = []) {
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        
        $response = ['success' => $success];
        
        if ($message !== null) {
            $response['message'] = $message;
        }
        
        if (!empty($data)) {
            if (is_array($data)) {
                $response = array_merge($response, $data);
            } else {
                $response['data'] = $data;
            }
        }
        
        try {
            $jsonOutput = json_encode($response);
            
            if ($jsonOutput === false) {
                $jsonError = json_last_error_msg();
                error_log("JSON encoding error: " . $jsonError);
                
                echo json_encode([
                    'success' => false,
                    'message' => 'Could not encode response data',
                    'error' => $jsonError
                ]);
            } else {
                echo $jsonOutput;
            }
        } catch (Exception $e) {
            error_log("Exception during JSON encoding: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'An error occurred while processing the response'
            ]);
        }
        
        exit;
    }
    

    private function sanitizeForJson($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->sanitizeForJson($value);
            }
            return $data;
        } elseif (is_object($data)) {
            $vars = get_object_vars($data);
            foreach ($vars as $key => $value) {
                $data->$key = $this->sanitizeForJson($value);
            }
            return $data;
        } else {
            return is_string($data) ? mb_convert_encoding($data, 'UTF-8', 'UTF-8') : $data;
        }
    }
    

    public function listAnimals() {
        try {
            $filters = [];
            if (isset($_GET['species'])) {
                $filters['species'] = $_GET['species'];
            }
            if (isset($_GET['gender'])) {
                $filters['gender'] = $_GET['gender'];
            }
            if (isset($_GET['age_min'])) {
                $filters['age_min'] = $_GET['age_min'];
            }
            if (isset($_GET['age_max'])) {
                $filters['age_max'] = $_GET['age_max'];
            }
            if (isset($_GET['location'])) {
                $filters['location'] = $_GET['location'];
            }
            
            $pets = $this->petModel->getPets($filters);
            
            if (!$pets) {
                return $this->sendJsonResponse(true, 'No pets found matching your criteria', ['pets' => []]);
            }
            
            foreach ($pets as &$pet) {

                if (!isset($pet['media']) || empty($pet['media'])) {
                    $pet['default_image'] = $this->getDefaultPetImage($pet['ID'], $pet['SPECIES']);
                }
            }
            
            return $this->sendJsonResponse(true, 'Pets retrieved successfully', ['pets' => $pets]);
            
        } catch (Exception $e) {
            error_log("Error in listAnimals: " . $e->getMessage());
            return $this->sendJsonResponse(false, 'An error occurred while retrieving pets');
        }
    }
    

    public function getPetDetails($petId) {
        try {
            if (!$petId) {
                return $this->sendJsonResponse(false, 'Pet ID is required');
            }
            
            $result = $this->showPetDetails($petId);
            
            if (isset($result['error'])) {
                return $this->sendJsonResponse(false, $result['error']);
            }
            
            $pet = $result['pet'];
            $media = $result['media'];
            
            if (empty($media)) {
                $pet['default_image'] = $this->getDefaultPetImage($pet['ID'], $pet['SPECIES']);
            }
            
            return $this->sendJsonResponse(true, 'Pet details retrieved successfully', [
                'pet' => $pet,
                'media' => $media
            ]);
            
        } catch (Exception $e) {
            error_log("Error in getPetDetails: " . $e->getMessage());
            return $this->sendJsonResponse(false, 'An error occurred while retrieving pet details');
        }
    }
    

    public function getPetLocations() {
        try {
            $result = $this->getAllPetsWithCoordinates();
            
            if (isset($result['error'])) {
                return $this->sendJsonResponse(false, $result['error']);
            }
            
            return $this->sendJsonResponse(true, 'Pet locations retrieved successfully', [
                'locations' => $result
            ]);
            
        } catch (Exception $e) {
            error_log("Error in getPetLocations: " . $e->getMessage());
            return $this->sendJsonResponse(false, 'An error occurred while retrieving pet locations');
        }
    }
    

    public function getPetPageData() {
        try {
            $petId = $_GET['id'] ?? null;
            
            if (!$petId) {
                return $this->sendJsonResponse(false, 'Pet ID is required');
            }
            
            $result = $this->showPetDetails($petId);
            
            if (isset($result['error'])) {
                return $this->sendJsonResponse(false, $result['error']);
            }
            
            $pet = $result['pet'];
            $media = $result['media'];
            
            $owner = $this->petModel->getPetOwner($petId);
            
            $response = [
                'pet' => $pet,
                'media' => $media,
                'owner' => $owner
            ];
            
            return $this->sendJsonResponse(true, 'Pet page data retrieved successfully', $response);
            
        } catch (Exception $e) {
            error_log("Error in getPetPageData: " . $e->getMessage());
            return $this->sendJsonResponse(false, 'An error occurred while retrieving pet page data');
        }
    }
    

    public function getUserPets($userId = null) {
        try {
            if (!$userId) {
                if (!isset($_SESSION['user_id'])) {
                    return $this->sendJsonResponse(false, 'User not authenticated');
                }
                $userId = $_SESSION['user_id'];
            }
            
            $result = $this->getUserPetsData($userId);
            
            if (isset($result['error'])) {
                return $this->sendJsonResponse(false, $result['error']);
            }
            
            foreach ($result as &$pet) {
                if (!isset($pet['media']) || empty($pet['media'])) {
                    $pet['default_image'] = $this->getDefaultPetImage($pet['ID'], $pet['SPECIES']);
                }
            }
            
            return $this->sendJsonResponse(true, 'User pets retrieved successfully', [
                'pets' => $result
            ]);
            
        } catch (Exception $e) {
            error_log("Error in getUserPets: " . $e->getMessage());
            return $this->sendJsonResponse(false, 'An error occurred while retrieving user pets');
        }
    }
    

    public function deletePet($petId) {
        try {
            if (!$petId) {
                return $this->sendJsonResponse(false, 'Pet ID is required');
            }
            
            $result = $this->deletePetData($petId);
            
            if (isset($result['error'])) {
                return $this->sendJsonResponse(false, $result['error']);
            }
            
            return $this->sendJsonResponse(true, 'Pet deleted successfully');
            
        } catch (Exception $e) {
            error_log("Error in deletePet: " . $e->getMessage());
            return $this->sendJsonResponse(false, 'An error occurred while deleting pet');
        }
    }
    

    public function createPet() {
        try {
            if (!isset($_SESSION['user_id'])) {
                return $this->sendJsonResponse(false, 'User not authenticated');
            }
            
            $data = $_POST;
            
            $data['owner_id'] = $_SESSION['user_id'];
            
            $result = $this->createPetData($data, $_FILES);
            
            if (isset($result['error'])) {
                return $this->sendJsonResponse(false, $result['error']);
            }
            
            return $this->sendJsonResponse(true, 'Pet created successfully', [
                'pet_id' => $result['pet_id']
            ]);
            
        } catch (Exception $e) {
            error_log("Error in createPet API: " . $e->getMessage());
            return $this->sendJsonResponse(false, 'An error occurred while creating pet');
        }
    }
}

if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    PetController::handleRequest();
}
