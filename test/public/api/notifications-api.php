<?php
/**
 * API Endpoint for Notification Operations
 * 
 * This endpoint delegates all notification operations to NotificationApiController.
 */

// Suppress any PHP errors and warnings
error_reporting(0);
ini_set('display_errors', 0);

// Start output buffering to capture any unexpected output
ob_start();

// Required files
require_once dirname(dirname(__DIR__)) . '/controllers/NotificationApiController.php';

// Headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

// Create controller instance
$controller = new NotificationApiController();

// Handle request based on method and action
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_notifications':
        $controller->getNotifications();
        break;
        
    case 'mark_read':
        $controller->markAsRead();
        break;
        
    case 'mark_all_read':
        $controller->markAllAsRead();
        break;
        
    case 'get_unread_count':
        $controller->getUnreadCount();
        break;
        
    default:
        // Clear output buffer
        ob_clean();
        
        // Send error response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action specified'
        ]);
        exit;
}
