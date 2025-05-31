<?php
    require_once __DIR__. '/../model/User.php';
    
    class AuthController{
        private function validateRegistration($data) {
            $errors = [];
            
            if (empty($data['name']) || strlen($data['name']) < 2) {
                $errors[] = "Numele trebuie să aibă cel puțin 2 caractere";
            }
            
            if (empty($data['surname']) || strlen($data['surname']) < 2) {
                $errors[] = "Prenumele trebuie să aibă cel puțin 2 caractere";
            }
            
            if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Adresa de email nu este validă";
            }
            
            if (empty($data['password']) || strlen($data['password']) < 6) {
                $errors[] = "Parola trebuie să aibă cel puțin 6 caractere";
            }
            
            if ($data['password'] !== $data['confirm_password']) {
                $errors[] = "Parolele nu coincid";
            }
            
            return $errors;
        }

        public function register($data){
            header('Content-Type: application/json');
            
            $errors = $this->validateRegistration($data);
            
            if (!empty($errors)) {
                echo json_encode(['success' => false, 'errors' => $errors]);
                exit();
            }

            $user = new User();
            $result = $user->createUser($data['name'], $data['surname'], $data['email'], $data['password']);
            
            if($result){
                echo json_encode(['success' => true]);
            } else {
                echo json_encode([
                    'success' => false, 
                    'errors' => ["Eroare la înregistrare. Încercați din nou."]
                ]);
            }
            exit();
        }

        public function login($data){
            header('Content-Type: application/json');
            
            if (empty($data['name']) || empty($data['surname']) || empty($data['password'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Toate câmpurile sunt obligatorii'
                ]);
                exit();
            }

            $user = new User();
            $authenticated = $user->authenticate($data['name'], $data['surname'], $data['password']);

            if($authenticated){
                session_start();
                $_SESSION['user_name'] = $data['name'];
                $_SESSION['user_surname'] = $data['surname'];
                echo json_encode([
                    'success' => true,
                    'redirect' => 'homepage.php'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Nume sau parolă incorecte'
                ]);
            }
            exit();
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller = new AuthController();
        
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'register':
                    $controller->register($_POST);
                    break;
                case 'login':
                    $controller->login($_POST);
                    break;
                default:
                    echo "Acțiune nevalidă";
                    exit();
            }
        }
    }