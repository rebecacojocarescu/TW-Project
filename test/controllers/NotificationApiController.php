        <?php
/**
 * NotificationApiController
 * 
 * Controller pentru gestionarea API-urilor legate de notificări
 */

error_reporting(0);
ini_set('display_errors', 0);

ob_start();

require_once dirname(dirname(__FILE__)) . '/config/database.php';
require_once dirname(dirname(__FILE__)) . '/controllers/NotificationController.php';

class NotificationApiController {
    private $conn;
    private $controller;
    
    /**
     * Constructor
     */
    public function __construct() {
        try {
            $this->conn = getConnection();
            $this->controller = new NotificationController();
        } catch (Exception $e) {
            error_log("Database connection error in NotificationApiController: " . $e->getMessage());
        }
    }
    
    /**
     * Destructor - make sure to close database connection
     */
    public function __destruct() {
        if ($this->conn) {
            oci_close($this->conn);
        }
    }
    
    /**
     * Helper function to send a JSON response
     */
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
                error_log("JSON encoding error: " . $jsonError);
                
                $fallbackResponse = json_encode([
                    'success' => false,
                    'message' => 'Error encoding response: ' . $jsonError
                ]);
                
                header('Content-Type: application/json');
                echo $fallbackResponse;
            } else {
                header('Content-Type: application/json');
                echo $jsonOutput;
            }
        } catch (Exception $e) {
            error_log("Exception during JSON response: " . $e->getMessage());
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'An error occurred while sending the response.'
            ]);
        }
        
        exit;
    }
    
    /**
     * Helper function to sanitize data for JSON encoding
     */
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
    
    /**
     * Get all notifications for a user
     */
    public function getNotifications() {
        try {
            if (!$this->conn) {
                return $this->sendJsonResponse(false, 'Database connection error');
            }
            
            session_start();
            if (!isset($_SESSION['user_id'])) {
                return $this->sendJsonResponse(false, 'User not authenticated');
            }
            $userId = $_SESSION['user_id'];
            $result = $this->controller->getNotifications();
            
            if (isset($result['success']) && $result['success'] && isset($result['notifications'])) {
                return $this->sendJsonResponse(true, 'Notifications retrieved successfully', [
                    'notifications' => $result['notifications']
                ]);
            } else {
                return $this->sendJsonResponse(false, 'Failed to retrieve notifications', []);
            }
            
        } catch (Exception $e) {
            error_log("Error in getNotifications: " . $e->getMessage());
            return $this->sendJsonResponse(false, 'An error occurred while retrieving notifications');
        }
    }
    
    /**
     * Mark a notification as read
     */
    public function markAsRead() {
        try {
            if (!$this->conn) {
                return $this->sendJsonResponse(false, 'Database connection error');
            }
            
            session_start();
            if (!isset($_SESSION['user_id'])) {
                return $this->sendJsonResponse(false, 'User not authenticated');
            }
            
            $requestData = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $notificationId = $requestData['notification_id'] ?? null;
            
            if (!$notificationId) {
                return $this->sendJsonResponse(false, 'Invalid notification ID');
            }
            
            $result = $this->controller->markAsRead($notificationId);
            
            if ($result) {
                return $this->sendJsonResponse(true, 'Notification marked as read');
            } else {
                return $this->sendJsonResponse(false, 'Failed to mark notification as read');
            }
            
        } catch (Exception $e) {
            error_log("Error in markAsRead: " . $e->getMessage());
            return $this->sendJsonResponse(false, 'An error occurred while marking notification as read');
        }
    }
    
    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead() {
        try {
            if (!$this->conn) {
                return $this->sendJsonResponse(false, 'Database connection error');
            }
            
            session_start();
            if (!isset($_SESSION['user_id'])) {
                return $this->sendJsonResponse(false, 'User not authenticated');
            }
            
            $result = $this->controller->markAllAsRead();
            
            if ($result) {
                return $this->sendJsonResponse(true, 'All notifications marked as read');
            } else {
                return $this->sendJsonResponse(false, 'Failed to mark all notifications as read');
            }
            
        } catch (Exception $e) {
            error_log("Error in markAllAsRead: " . $e->getMessage());
            return $this->sendJsonResponse(false, 'An error occurred while marking all notifications as read');
        }
    }
    
    /**
     * Get unread notification count for a user
     */
    public function getUnreadCount() {
        try {
            if (!$this->conn) {
                return $this->sendJsonResponse(false, 'Database connection error');
            }
            
            session_start();
            if (!isset($_SESSION['user_id'])) {
                return $this->sendJsonResponse(false, 'User not authenticated');
            }
            $userId = $_SESSION['user_id'];
            
            $count = $this->controller->getUnreadCount($userId);
            
            return $this->sendJsonResponse(true, 'Unread notification count retrieved successfully', [
                'count' => $count
            ]);
            
        } catch (Exception $e) {
            error_log("Error in getUnreadCount: " . $e->getMessage());
            return $this->sendJsonResponse(false, 'An error occurred while retrieving unread notification count');
        }
    }
}
