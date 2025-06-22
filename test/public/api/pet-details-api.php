<?php
/**
 * Pet Details API Endpoint
 * Acest endpoint furnizează detalii despre un animal specific
 */

// Define a custom error handler for debugging
function apiErrorHandler($severity, $message, $file, $line) {
    // Log the error to a file instead of displaying it
    error_log("PHP Error ($severity): $message in $file on line $line");
    // Don't display the error
    return true;
}

// Set custom error handler
set_error_handler('apiErrorHandler');

// Suppress any PHP errors and warnings
error_reporting(0);
ini_set('display_errors', 0);

// Start output buffering to capture any unexpected output
ob_start();

// CORS headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Load the controller
require_once '../../controllers/PetApiController.php';

// Get pet details by ID
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get') {
    try {
        if (!isset($_GET['id'])) {
            sendJsonResponse(false, 'Pet ID is required');
        }
        
        $petId = (int)$_GET['id'];
        if ($petId <= 0) {
            sendJsonResponse(false, 'Invalid pet ID');
        }
        
        $conn = getConnection();
        if (!$conn) {
            sendJsonResponse(false, 'Could not connect to database');
        }
        
        // Get pet details
        $pet = new Pet($conn);
        $petDetails = $pet->getPetById($petId);
        
        if (!$petDetails) {
            sendJsonResponse(false, 'Pet not found');
        }
        
        // Get pet images
        $media = $pet->getPetMedia($petId);
        
        // Get all pet locations for map (with error handling)
        try {
            $allPetsWithCoordinates = $pet->getAllPetsWithCoordinates();
            if (!is_array($allPetsWithCoordinates)) {
                $allPetsWithCoordinates = [];
            }
        } catch (Exception $e) {
            // Log the error but continue with empty array
            error_log("Error fetching pet coordinates: " . $e->getMessage());
            $allPetsWithCoordinates = [];
        }
        
        // Get pet restrictions (with error handling)
        try {
            $restrictions = $pet->getPetRestrictions($petId);
        } catch (Exception $e) {
            $restrictions = [];
        }
        
        // Get feeding schedule (with error handling)
        try {
            $feedingSchedule = $pet->getPetFeedingSchedule($petId);
        } catch (Exception $e) {
            $feedingSchedule = [];
        }
        
        // Get medical history if authorized (with error handling)
        $medicalHistory = []; // Default empty
        if (isset($_GET['include_medical']) && $_GET['include_medical'] == 'true') {
            try {
                // Additional authorization check could be added here
                $medicalHistory = $pet->getPetMedicalHistory($petId);
            } catch (Exception $e) {
                $medicalHistory = [];
            }
        }
        
        sendJsonResponse(true, null, [
            'pet' => $petDetails,
            'media' => $media,
            'allPetsWithCoordinates' => $allPetsWithCoordinates,
            'restrictions' => $restrictions,
            'feedingSchedule' => $feedingSchedule,
            'medicalHistory' => $medicalHistory
        ]);
    } catch (Exception $e) {
        sendJsonResponse(false, 'Error processing request: ' . $e->getMessage());
    }
}

// Get recent pets (for homepage)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'recent') {
    try {
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 6;
        $conn = getConnection();
        
        if (!$conn) {
            sendJsonResponse(false, 'Could not connect to database');
        }
        
        $query = "SELECT p.id, p.name, p.species, p.breed, p.gender, p.age, p.available_for_adoption 
                 FROM pets p 
                 WHERE p.available_for_adoption = 1 
                 ORDER BY p.id DESC 
                 FETCH FIRST :limit ROWS ONLY";
                 
        $stmt = oci_parse($conn, $query);
        if (!$stmt) {
            sendJsonResponse(false, 'Failed to prepare database statement');
        }
        
        oci_bind_by_name($stmt, ":limit", $limit);
        $result = oci_execute($stmt);
        
        if (!$result) {
            $error = oci_error($stmt);
            sendJsonResponse(false, 'Database query error: ' . $error['message']);
        }
        
        $pets = [];
        while ($row = oci_fetch_assoc($stmt)) {
            try {
                // Get pet image
                $imgQuery = "SELECT url FROM media WHERE pet_id = :pet_id AND type = 'photo' AND ROWNUM = 1";
                $imgStmt = oci_parse($conn, $imgQuery);
                oci_bind_by_name($imgStmt, ":pet_id", $row['ID']);
                oci_execute($imgStmt);
                $image = oci_fetch_assoc($imgStmt);
                oci_free_statement($imgStmt);
                
                $pets[] = [
                    'id' => $row['ID'],
                    'name' => $row['NAME'],
                    'species' => $row['SPECIES'],
                    'breed' => $row['BREED'],
                    'gender' => $row['GENDER'],
                    'age' => $row['AGE'],
                    'image' => ($image && isset($image['URL'])) ? '../' . $image['URL'] : '../stiluri/imagini/' . strtolower($row['SPECIES']) . '.png'
                ];
            } catch (Exception $e) {
                // Skip this pet if there's an error with it
                continue;
            }
        }
        oci_free_statement($stmt);
        oci_close($conn);
        
        sendJsonResponse(true, null, ['pets' => $pets]);
    } catch (Exception $e) {
        sendJsonResponse(false, 'Error processing request: ' . $e->getMessage());
    }
}

// Invalid request
sendJsonResponse(false, 'Invalid request');
