<?php

error_reporting(0);
ini_set('display_errors', 0);

ob_start();

require_once dirname(dirname(__FILE__)) . '/config/database.php';
require_once dirname(dirname(__FILE__)) . '/models/AdoptionForm.php';
require_once dirname(dirname(__FILE__)) . '/models/AdoptionRequest.php';
require_once dirname(dirname(__FILE__)) . '/models/Pet.php';

class AdoptionApiController {
    private $conn;
    private $adoptionFormModel;
    private $adoptionRequestModel;
    private $petModel;
    
    public function __construct() {
        try {
            $this->conn = getConnection();
            $this->adoptionFormModel = new AdoptionForm($this->conn);
            $this->adoptionRequestModel = new AdoptionRequest($this->conn);
            $this->petModel = new Pet($this->conn);
        } catch (Exception $e) {
            error_log("Database connection error in AdoptionApiController: " . $e->getMessage());
        }
    }
    
    public function __destruct() {
        if ($this->conn) {
            oci_close($this->conn);
        }
    }
    

    private function sendJsonResponse($success, $message = null, $data = []) {
        ob_clean();
        
        $response = ['success' => $success];
        
        if ($message !== null) {
            $response['message'] = $message;
        }
        
        if (!empty($data)) {
            $data = $this->sanitizeForJson($data);
            $response = array_merge($response, $data);
        }
        
        try {
            $jsonOutput = json_encode($response);
            
            if ($jsonOutput === false) {
                $jsonError = json_last_error_msg();
                error_log("JSON encoding error in AdoptionApiController: " . $jsonError);
                
                echo json_encode([
                    'success' => false,
                    'message' => 'Could not encode response data',
                    'error' => $jsonError
                ]);
            } else {
                echo $jsonOutput;
            }
        } catch (Exception $e) {
            error_log("Exception during JSON encoding in AdoptionApiController: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'An error occurred while processing the response'
            ]);
        }
        
        exit;
    }
    
    /**
     * Recursively sanitize data to ensure it can be safely JSON encoded
     */
    private function sanitizeForJson($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->sanitizeForJson($value);
            }
            return $data;
        } elseif (is_object($data)) {
            $data = (array)$data;
            foreach ($data as $key => $value) {
                $data[$key] = $this->sanitizeForJson($value);
            }
            return $data;
        } elseif (is_string($data)) {
            if (!mb_check_encoding($data, 'UTF-8')) {
                return mb_convert_encoding($data, 'UTF-8', 'ISO-8859-1');
            }
            return $data;
        } elseif (is_resource($data)) {
            return "Resource";
        } else {
            return $data;
        }
    }
    
    /**
     * Verifică dacă există o cerere de adopție pentru un utilizator și un animal
     */
    public function checkExistingRequest() {
        try {
            if (!$this->conn) {
                $this->sendJsonResponse(false, 'Database connection failed');
            }
            
            if (!isset($_GET['user_id']) || !isset($_GET['pet_id'])) {
                $this->sendJsonResponse(false, 'User ID and Pet ID are required');
            }
            
            $userId = (int)$_GET['user_id'];
            $petId = (int)$_GET['pet_id'];
            
            if ($userId <= 0 || $petId <= 0) {
                $this->sendJsonResponse(false, 'Invalid User ID or Pet ID');
            }
            
            $existingRequest = $this->adoptionRequestModel->getRequestByUserAndPet($userId, $petId);
            
            if ($existingRequest) {
                $this->sendJsonResponse(true, 'Adoption request already exists', [
                    'exists' => true,
                    'request' => $existingRequest
                ]);
            } else {
                $this->sendJsonResponse(true, 'No adoption request found', [
                    'exists' => false
                ]);
            }
            
        } catch (Exception $e) {
            error_log("Error checking adoption request: " . $e->getMessage());
            $this->sendJsonResponse(false, 'Error checking adoption request: ' . $e->getMessage());
        }
    }
    
    /**
     * Submite un formular de adopție
     */
    public function submitAdoptionForm() {
        try {
            if (!$this->conn) {
                $this->sendJsonResponse(false, 'Database connection failed');
            }
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->sendJsonResponse(false, 'Invalid request method');
            }
            
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            
            if (!$data) {
                $this->sendJsonResponse(false, 'Invalid JSON data');
            }
            
            $requiredFields = [
                'user_id', 'pet_id', 'nume_complet', 'varsta', 'ocupatie', 
                'adresa', 'telefon', 'email', 'experienta', 'locuinta_tip', 
                'locuinta_proprietar', 'spatiu_exterior', 'copii', 
                'alte_animale', 'veterinar', 'ingrijire_financiara', 
                'timp_singur', 'perioada_adaptare', 'vaccinuri', 
                'castrare', 'renuntare_motive', 'declaratie'
            ];
            
            $missingFields = [];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || (is_string($data[$field]) && empty(trim($data[$field])))) {
                    $missingFields[] = $field;
                }
            }
            
            if (!empty($missingFields)) {
                $this->sendJsonResponse(false, 'Missing required fields: ' . implode(', ', $missingFields));
            }
            
            $existingRequest = $this->adoptionRequestModel->getRequestByUserAndPet($data['user_id'], $data['pet_id']);
            if ($existingRequest) {
                $this->sendJsonResponse(false, 'You have already submitted an adoption request for this pet');
            }
            
            $formId = $this->adoptionFormModel->createAdoptionForm($data);
            if (!$formId) {
                $this->sendJsonResponse(false, 'Failed to create adoption form');
            }
            
            $requestData = [
                'user_id' => $data['user_id'],
                'pet_id' => $data['pet_id'],
                'form_id' => $formId,
                'status' => 'pending'
            ];
            
            $requestId = $this->adoptionRequestModel->createAdoptionRequest($requestData);
            if (!$requestId) {
                $this->sendJsonResponse(false, 'Failed to create adoption request');
            }
            
            $this->sendJsonResponse(true, 'Adoption form submitted successfully', [
                'form_id' => $formId,
                'request_id' => $requestId
            ]);
            
        } catch (Exception $e) {
            error_log("Error submitting adoption form: " . $e->getMessage());
            $this->sendJsonResponse(false, 'Error submitting adoption form: ' . $e->getMessage());
        }
    }
    
    /**
     * Obține toate cererile de adopție pentru un utilizator
     */
    public function getUserAdoptionRequests() {
        try {
            if (!$this->conn) {
                $this->sendJsonResponse(false, 'Database connection failed');
            }
            
            if (!isset($_GET['user_id'])) {
                $this->sendJsonResponse(false, 'User ID is required');
            }
            
            $userId = (int)$_GET['user_id'];
            if ($userId <= 0) {
                $this->sendJsonResponse(false, 'Invalid User ID');
            }
            
            $requests = $this->adoptionRequestModel->getRequestsByUserId($userId);
            
            $enhancedRequests = [];
            foreach ($requests as $request) {
                $petDetails = $this->petModel->getPetById($request['PET_ID']);
                $petImage = '';
                
                try {
                    $media = $this->petModel->getPetMedia($request['PET_ID']);
                    if (!empty($media)) {
                        $petImage = '../' . $media[0]['URL'];
                    } else {
                        $petImage = '../stiluri/imagini/' . strtolower($petDetails['SPECIES']) . '.png';
                    }
                } catch (Exception $e) {
                    $petImage = '../stiluri/imagini/' . strtolower($petDetails['SPECIES'] ?? 'dog') . '.png';
                }
                
                $enhancedRequests[] = array_merge($request, [
                    'PET_DETAILS' => $petDetails,
                    'PET_IMAGE' => $petImage
                ]);
            }
            
            $this->sendJsonResponse(true, null, [
                'requests' => $enhancedRequests
            ]);
            
        } catch (Exception $e) {
            error_log("Error getting adoption requests: " . $e->getMessage());
            $this->sendJsonResponse(false, 'Error getting adoption requests: ' . $e->getMessage());
        }
    }
    
    public function getOwnerAdoptionRequests() {
        try {
            if (!$this->conn) {
                $this->sendJsonResponse(false, 'Database connection failed');
            }
            
            if (!isset($_GET['owner_id'])) {
                $this->sendJsonResponse(false, 'Owner ID is required');
            }
            
            $ownerId = (int)$_GET['owner_id'];
            if ($ownerId <= 0) {
                $this->sendJsonResponse(false, 'Invalid Owner ID');
            }
            
            $pets = $this->petModel->getPetsByUserId($ownerId);
            
            $allRequests = [];
            foreach ($pets as $pet) {
                $requests = $this->adoptionRequestModel->getRequestsByPetId($pet['ID']);
                
                foreach ($requests as $request) {
                    $request['PET_DETAILS'] = $pet;
                    
                    try {
                        $media = $this->petModel->getPetMedia($pet['ID']);
                        if (!empty($media)) {
                            $request['PET_IMAGE'] = '../' . $media[0]['URL'];
                        } else {
                            $request['PET_IMAGE'] = '../stiluri/imagini/' . strtolower($pet['SPECIES']) . '.png';
                        }
                    } catch (Exception $e) {
                        $request['PET_IMAGE'] = '../stiluri/imagini/' . strtolower($pet['SPECIES']) . '.png';
                    }
                    
                    $allRequests[] = $request;
                }
            }
            
            $this->sendJsonResponse(true, null, [
                'requests' => $allRequests
            ]);
            
        } catch (Exception $e) {
            error_log("Error getting owner's adoption requests: " . $e->getMessage());
            $this->sendJsonResponse(false, 'Error getting owner\'s adoption requests: ' . $e->getMessage());
        }
    }
    
    /**
     * Obține detaliile unui formular de adopție
     */
    public function getAdoptionFormDetails() {
        try {
            if (!$this->conn) {
                $this->sendJsonResponse(false, 'Database connection failed');
            }
            
            if (!isset($_GET['form_id'])) {
                $this->sendJsonResponse(false, 'Form ID is required');
            }
            
            $formId = (int)$_GET['form_id'];
            if ($formId <= 0) {
                $this->sendJsonResponse(false, 'Invalid Form ID');
            }
            
            $formDetails = $this->adoptionFormModel->getAdoptionFormById($formId);
            if (!$formDetails) {
                $this->sendJsonResponse(false, 'Form not found');
            }
            
            $this->sendJsonResponse(true, null, [
                'form' => $formDetails
            ]);
            
        } catch (Exception $e) {
            error_log("Error getting adoption form: " . $e->getMessage());
            $this->sendJsonResponse(false, 'Error getting adoption form: ' . $e->getMessage());
        }
    }
    
    /**
     * Actualizează statusul unei cereri de adopție
     */
    public function updateAdoptionStatus() {
        try {
            if (!$this->conn) {
                $this->sendJsonResponse(false, 'Database connection failed');
            }
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->sendJsonResponse(false, 'Invalid request method');
            }
            
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            
            if (!$data) {
                $this->sendJsonResponse(false, 'Invalid JSON data');
            }
            
            if (!isset($data['request_id']) || !isset($data['status'])) {
                $this->sendJsonResponse(false, 'Request ID and status are required');
            }
            
            $requestId = (int)$data['request_id'];
            $status = $data['status'];
            $notes = isset($data['notes']) ? $data['notes'] : '';
            
            $validStatuses = ['pending', 'approved', 'rejected', 'completed'];
            if (!in_array($status, $validStatuses)) {
                $this->sendJsonResponse(false, 'Invalid status value');
            }
            
            $currentRequest = $this->adoptionRequestModel->getRequestById($requestId);
            if (!$currentRequest) {
                $this->sendJsonResponse(false, 'Adoption request not found');
            }
            
            $success = $this->adoptionRequestModel->updateAdoptionStatus($requestId, $status, $notes);
            if (!$success) {
                $this->sendJsonResponse(false, 'Failed to update adoption status');
            }
            
            if ($status === 'approved') {
                try {
                    $this->petModel->setPetAvailability($currentRequest['PET_ID'], false);
                } catch (Exception $e) {
                    error_log("Error updating pet availability: " . $e->getMessage());
                }
            }
            
            $this->sendJsonResponse(true, 'Adoption status updated successfully');
            
        } catch (Exception $e) {
            error_log("Error updating adoption status: " . $e->getMessage());
            $this->sendJsonResponse(false, 'Error updating adoption status: ' . $e->getMessage());
        }
    }
}
