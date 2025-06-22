<?php
require_once '../config/database.php';

class NotificationController {
    private $conn;

    public function __construct() {
        $this->conn = getConnection();
    }    public function getNotifications() {
        if (!isset($_SESSION['user_id'])) {
            return [
                'success' => false,
                'error' => 'User not authenticated'
            ];
        }

        try {
            $query = "SELECT n.*, 
                            p.name as pet_name, 
                            p.species as pet_species
                     FROM user_notifications n
                     LEFT JOIN pets p ON n.related_pet_id = p.id
                     WHERE n.user_id = :user_id
                     ORDER BY n.created_at DESC";

            $stmt = oci_parse($this->conn, $query);
            oci_bind_by_name($stmt, ":user_id", $_SESSION['user_id']);
            oci_execute($stmt);

            $notifications = [];
            while ($row = oci_fetch_assoc($stmt)) {
                $notifications[] = $row;
            }

            return [
                'success' => true,
                'notifications' => $notifications
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getUnreadCount() {
        if (!isset($_SESSION['user_id'])) {
            return 0;
        }

        try {
            $query = "SELECT COUNT(*) as count 
                     FROM user_notifications 
                     WHERE user_id = :user_id 
                     AND is_read = 0";

            $stmt = oci_parse($this->conn, $query);
            oci_bind_by_name($stmt, ":user_id", $_SESSION['user_id']);
            oci_execute($stmt);

            $row = oci_fetch_assoc($stmt);
            return $row['COUNT'];

        } catch (Exception $e) {
            return 0;
        }
    }

    public function markAsRead($notification_id) {
        try {
            $query = "UPDATE user_notifications 
                     SET is_read = 1 
                     WHERE id = :notification_id 
                     AND user_id = :user_id";

            $stmt = oci_parse($this->conn, $query);
            oci_bind_by_name($stmt, ":notification_id", $notification_id);
            oci_bind_by_name($stmt, ":user_id", $_SESSION['user_id']);
            
            if (oci_execute($stmt)) {
                return ['success' => true];
            }
            
            return ['success' => false, 'error' => 'Could not mark notification as read'];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function markAllAsRead() {
        try {
            $query = "UPDATE user_notifications 
                     SET is_read = 1 
                     WHERE user_id = :user_id";

            $stmt = oci_parse($this->conn, $query);
            oci_bind_by_name($stmt, ":user_id", $_SESSION['user_id']);
            
            if (oci_execute($stmt)) {
                return ['success' => true];
            }
            
            return ['success' => false, 'error' => 'Could not mark notifications as read'];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

} 