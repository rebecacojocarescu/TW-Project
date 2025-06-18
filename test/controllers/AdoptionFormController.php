<?php
require_once '../models/AdoptionForm.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_id']) && isset($_POST['status'])) {
    header('Content-Type: application/json');
    $controller = new AdoptionFormController();
    $result = $controller->updateStatus($_POST['form_id'], $_POST['status']);
    echo json_encode($result);
    exit;
}

class AdoptionFormController {
    private $model;

    public function __construct() {
        $this->model = new AdoptionForm();
    }

    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: login.php");
            exit;
        }
    }

    public function processForm($postData, $petId, $userId) {
        try {
            $formData = array_merge($postData, [
                'pet_id' => $petId,
                'user_id' => $userId
            ]);

            $this->model->submitForm($formData);
            
            header("Location: adoption-success.php");
            exit;
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function validateForm($data) {
        $errors = [];

        $requiredFields = [
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'email' => 'Email',
            'phone' => 'Phone Number',
            'street_address' => 'Street Address',
            'city' => 'City',
            'country' => 'Country',
            'postal_code' => 'Postal Code',
            'pet_name' => 'Pet Name',
            'yard' => 'Yard Information',
            'housing' => 'Housing Status',
            'adoption_reason' => 'Adoption Reason'
        ];

        foreach ($requiredFields as $field => $label) {
            if (empty($data[$field])) {
                $errors[] = "$label is required";
            }
        }

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }

        if (!empty($data['phone']) && !preg_match("/^[0-9()+\- ]{7,20}$/", $data['phone'])) {
            $errors[] = "Invalid phone number format";
        }

        return $errors;
    }

    public function updateStatus($formId, $status) {
        try {
            if (empty($formId) || empty($status)) {
                return [
                    'success' => false,
                    'message' => 'Missing required parameters'
                ];
            }

            $formId = (int)$formId;
            if (!in_array($status, ['approved', 'rejected'])) {
                return [
                    'success' => false,
                    'message' => 'Invalid status'
                ];
            }

            $result = $this->model->updateStatus($formId, $status);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Status updated successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to update status'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
} 