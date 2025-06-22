<?php
require_once dirname(dirname(__FILE__)) . '/config/database.php';
require_once dirname(dirname(__FILE__)) . '/models/User.php';

class UserApiController {
    private $conn;
    private $userModel;
    
    public function __construct() {
        error_reporting(E_ALL);
        ini_set('display_errors', 0);
        ini_set('log_errors', 1);
        
        while (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();
        
        try {
            $this->conn = getConnection();
            $this->userModel = new User();
        } catch (Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
        }
    }
    

    public function __destruct() {
        if ($this->conn) {
            oci_close($this->conn);
        }
    }
    
  protected function sendJsonResponse($success, $message = '', $data = null) {
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        
        $response = [
            'success' => $success,
            'message' => $message
        ];
        
        if ($data !== null) {
            if (isset($data['success'])) {
                $response = array_merge($response, $data);
            } else {
                $response['data'] = $data;
            }
        }
        

        $jsonResponse = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        
        if ($jsonResponse === false) {
            error_log("JSON encode error: " . json_last_error_msg());
            echo json_encode([
                'success' => false,
                'message' => 'Failed to encode response',
                'error' => json_last_error_msg()
            ]);
            return;
        }
        
        echo $jsonResponse;
    }    public function getUserProfile($userId = null) {
        try {
            error_log("Starting getUserProfile for userId: " . $userId);
            
            while (ob_get_level()) {
                ob_end_clean();
            }
            ob_start();
            
            if (!$this->conn) {
                error_log("Database connection failed in getUserProfile");
                return $this->sendJsonResponse(false, 'Database connection failed');
            }
            
            if ($userId === null) {
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $userId = $_SESSION['user_id'] ?? null;
                error_log("Using session user_id: " . $userId);
            }
            
            if (!$userId) {
                error_log("No user ID provided or found in session");
                return $this->sendJsonResponse(false, 'User ID is required');
            }
            
            $userId = (int)$userId;
            error_log("Fetching user profile for ID: " . $userId);
            
            $userProfile = $this->userModel->getUserById($userId);
            
            if (!$userProfile) {
                error_log("User profile not found for ID: " . $userId);
                return $this->sendJsonResponse(false, 'User not found');
            }
            
            error_log("User profile retrieved successfully: " . json_encode($userProfile, JSON_UNESCAPED_UNICODE));
            
            error_log("Fetching user stats...");
            try {
                $stats = $this->getUserStats($userId);
                error_log("User stats retrieved: " . json_encode($stats, JSON_UNESCAPED_UNICODE));
            } catch (Exception $e) {
                error_log("Error getting user stats: " . $e->getMessage());
                $stats = [
                    'postedPets' => 0,
                    'adoptedPets' => 0
                ];
            }
            
            error_log("Fetching adopted pets...");
            try {
                $query = "SELECT p.*, ar.status
                         FROM pets p
                         INNER JOIN adoption_requests ar ON p.id = ar.pet_id
                         WHERE ar.user_id = ? AND ar.status = 'approved'
                         ORDER BY ar.created_at DESC";
                
                $stmt = $this->conn->prepare($query);
                if (!$stmt) {
                    throw new Exception("Failed to prepare adopted pets query: " . $this->conn->error);
                }
                
                $stmt->bind_param('i', $userId);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to execute adopted pets query: " . $stmt->error);
                }
                
                $result = $stmt->get_result();
                $adoptedPets = [];
                
                while ($row = $result->fetch_assoc()) {
                    $row['id'] = (int)$row['id'];
                    $row['user_id'] = (int)$row['user_id'];
                    $row['age'] = (int)$row['age'];
                    if (isset($row['lat'])) $row['lat'] = (float)$row['lat'];
                    if (isset($row['lng'])) $row['lng'] = (float)$row['lng'];
                    
                    $imagesQuery = "SELECT image_path FROM pet_images WHERE pet_id = ?";
                    $imagesStmt = $this->conn->prepare($imagesQuery);
                    if ($imagesStmt) {
                        $imagesStmt->bind_param('i', $row['id']);
                        $imagesStmt->execute();
                        $imagesResult = $imagesStmt->get_result();
                        $row['images'] = [];
                        while ($image = $imagesResult->fetch_assoc()) {
                            $row['images'][] = $image['image_path'];
                        }
                        $imagesStmt->close();
                    }
                    
                    $adoptedPets[] = $row;
                }
                
                error_log("Adopted pets retrieved: " . json_encode($adoptedPets, JSON_UNESCAPED_UNICODE));
                $stmt->close();
                
            } catch (Exception $e) {
                error_log("Error fetching adopted pets: " . $e->getMessage());
                $adoptedPets = [];
            }
            
            $responseData = [
                'success' => true,
                'data' => [
                    'user' => $userProfile,
                    'stats' => $stats,
                    'adoptedPets' => $adoptedPets
                ]
            ];
            
            error_log("Sending final response: " . json_encode($responseData, JSON_UNESCAPED_UNICODE));
            return $this->sendJsonResponse(true, '', $responseData);
            
        } catch (Exception $e) {
            error_log("Unexpected error in getUserProfile: " . $e->getMessage());
            return $this->sendJsonResponse(false, 'An unexpected error occurred');
        } finally {
            while (ob_get_level()) {
                ob_end_clean();
            }
        }
    }
    

    public function updateProfile() {
        try {
            ob_clean();
            
            if (!$this->conn) {
                $this->sendJsonResponse(false, 'Database connection failed');
                return;
            }
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->sendJsonResponse(false, 'This endpoint only accepts POST requests');
                return;
            }
            
            session_start();
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                $this->sendJsonResponse(false, 'User not authenticated');
                return;
            }
            
            $userData = $_POST;
            
            $allowedFields = ['name', 'surname', 'email', 'password', 'address', 'phone', 'description'];
            $updateData = [];
            
            foreach ($allowedFields as $field) {
                if (isset($userData[$field]) && !empty($userData[$field])) {
                    $updateData[$field] = $userData[$field];
                }
            }
            
            if (isset($updateData['password'])) {
                $updateData['password'] = password_hash($updateData['password'], PASSWORD_DEFAULT);
            }
            
            if (empty($updateData)) {
                $this->sendJsonResponse(false, 'No fields to update');
                return;
            }
            
            $setClause = [];
            foreach ($updateData as $field => $value) {
                $setClause[] = "{$field} = :{$field}";
            }
            
            $query = "UPDATE users SET " . implode(', ', $setClause) . " WHERE id = :user_id";
            $stmt = oci_parse($this->conn, $query);
            
            if (!$stmt) {
                $error = oci_error($this->conn);
                $this->sendJsonResponse(false, 'Failed to prepare update statement: ' . $error['message']);
                return;
            }
            
            foreach ($updateData as $field => $value) {
                oci_bind_by_name($stmt, ":{$field}", $updateData[$field]);
            }
            oci_bind_by_name($stmt, ":user_id", $userId);
            
            $result = oci_execute($stmt);
            
            if (!$result) {
                $error = oci_error($stmt);
                $this->sendJsonResponse(false, 'Failed to update profile: ' . $error['message']);
                return;
            }
            
            $this->sendJsonResponse(true, 'Profile updated successfully');
            
        } catch (Exception $e) {
            error_log("Error in updateProfile: " . $e->getMessage());
            $this->sendJsonResponse(false, 'An error occurred while updating profile');
        }
    }

    public function getUserStats($userId = null) {
        try {
            ob_clean();
            
            if (!$this->conn) {
                $this->sendJsonResponse(false, 'Database connection failed');
                return;
            }
            
            if ($userId === null) {
                session_start();
                $userId = $_SESSION['user_id'] ?? null;
            }
            
            if (!$userId) {
                $this->sendJsonResponse(false, 'User ID is required');
                return;
            }
            
            $petQuery = "SELECT COUNT(*) AS pet_count FROM pets WHERE user_id = :user_id";
            $petStmt = oci_parse($this->conn, $petQuery);
            oci_bind_by_name($petStmt, ":user_id", $userId);
            oci_execute($petStmt);
            $petRow = oci_fetch_assoc($petStmt);
            $petCount = isset($petRow['PET_COUNT']) ? $petRow['PET_COUNT'] : 0;
            
            $requestQuery = "SELECT COUNT(*) AS request_count FROM adoption_requests WHERE requester_id = :user_id";
            $requestStmt = oci_parse($this->conn, $requestQuery);
            oci_bind_by_name($requestStmt, ":user_id", $userId);
            oci_execute($requestStmt);
            $requestRow = oci_fetch_assoc($requestStmt);
            $requestCount = isset($requestRow['REQUEST_COUNT']) ? $requestRow['REQUEST_COUNT'] : 0;
            
            $adoptionQuery = "SELECT COUNT(*) AS adoption_count FROM adoption_status 
                             WHERE requester_id = :user_id AND status = 'approved'";
            $adoptionStmt = oci_parse($this->conn, $adoptionQuery);
            oci_bind_by_name($adoptionStmt, ":user_id", $userId);
            oci_execute($adoptionStmt);
            $adoptionRow = oci_fetch_assoc($adoptionStmt);
            $adoptionCount = isset($adoptionRow['ADOPTION_COUNT']) ? $adoptionRow['ADOPTION_COUNT'] : 0;
            
            $this->sendJsonResponse(true, null, [
                'stats' => [
                    'petCount' => $petCount,
                    'requestCount' => $requestCount,
                    'adoptionCount' => $adoptionCount
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("Error in getUserStats: " . $e->getMessage());
            $this->sendJsonResponse(false, 'An error occurred while retrieving user stats');
        }
    }
    

    private function getAdoptedPets($userId) {
        $adoptedPets = [];
        
        try {
            $query = "SELECT DISTINCT
                        p.id,
                        p.name,
                        p.species,
                        p.breed,
                        p.color,
                        p.marime,
                        CASE 
                            WHEN a.adoption_date IS NOT NULL THEN TO_CHAR(a.adoption_date, 'YYYY-MM-DD')
                            ELSE TO_CHAR(ast.created_at, 'YYYY-MM-DD')
                        END as adoption_date,
                        NVL((SELECT m.url 
                            FROM media m 
                            WHERE m.pet_id = p.id 
                            AND ROWNUM = 1), 'stiluri/imagini/' || LOWER(p.species) || '.png') as pet_image
                    FROM pets p                    LEFT JOIN adoptions a ON a.pet_id = p.id AND a.status = 'approved' AND a.adopter_id = :user_id
                    LEFT JOIN adoption_status ast ON ast.pet_id = p.id AND ast.status = 'approved' AND ast.requester_id = :user_id
                    WHERE a.pet_id IS NOT NULL OR ast.pet_id IS NOT NULL
                    ORDER BY adoption_date DESC";

            error_log("Executing adopted pets query for user ID: " . $userId);
            $stmt = oci_parse($this->conn, $query);
            
            if (!$stmt) {
                throw new Exception("Failed to parse adopted pets query: " . print_r(oci_error($this->conn), true));
            }
            
            $userIdNum = (int)$userId;
            oci_bind_by_name($stmt, ":user_id", $userIdNum);
            
            if (!oci_execute($stmt)) {
                throw new Exception("Error executing adopted pets query: " . print_r(oci_error($stmt), true));
            }
            
            while ($row = oci_fetch_assoc($stmt)) {
                $row['ID'] = (int)$row['ID'];
                
                if (!empty($row['PET_IMAGE']) && $row['PET_IMAGE'] !== 'stiluri/imagini/' . strtolower($row['SPECIES']) . '.png') {
                    $row['PET_IMAGE'] = '../' . trim($row['PET_IMAGE']);
                } else {
                    $row['PET_IMAGE'] = '../stiluri/imagini/' . strtolower($row['SPECIES']) . '.png';
                }
                
                $adoptedPets[] = $row;
            }
            
            error_log("Found " . count($adoptedPets) . " adopted pets for user " . $userId);
            
        } catch (Exception $e) {
            error_log("Error in getAdoptedPets: " . $e->getMessage());
            throw $e;
        } finally {
            if (isset($stmt)) {
                oci_free_statement($stmt);
            }
        }
        
        return $adoptedPets;
    }
}
