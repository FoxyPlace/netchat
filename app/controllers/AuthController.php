<?php
require_once __DIR__ . '/../../core/BaseController.php';
require_once __DIR__ . '/../models/User.php';

class AuthController extends BaseController {
    private $userModel;
    
    public function __construct() {
        parent::__construct();
        $this->userModel = new User($this->db);
    }
    
    public function login() {
        // Redir si déjà connecté
        if (isset($_SESSION['user_id'])) {
            // Use absolute public path to avoid routing issues in local env
            $this->redirect('/netchat/public/');
        }
        
        $error = '';
        $identifier_value = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $identifier = trim($_POST['identifier'] ?? '');
            $password = $_POST['password'] ?? '';
            $identifier_value = $identifier;
            
            $user = $this->userModel->findByUsernameOrEmail($identifier);
            
            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $this->redirect('/netchat/public/');
            } else {
                $error = "Identifiants incorrects";
            }
        }
        
        $this->view('auth/login', ['error' => $error, 'identifier_value' => $identifier_value]);
    }
    
    public function register() {
        // Redir si déjà connecté
        if (isset($_SESSION['user_id'])) {
            // Use absolute public path to avoid routing issues in local env
            $this->redirect('/netchat/public/');
        }
        
        $errorMessages = [];
        $success = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'register') {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            $phone = trim($_POST['phone'] ?? '');
            $birthdate = $_POST['birthdate'] ?? '';
            
            $errors = $this->validateRegistration($username, $email, $password, $confirm_password, $phone, $birthdate);
            
            if (empty($errors)) {
                // Vérifier doublons
                if ($this->userModel->usernameExists($username) || $this->userModel->emailExists($email)) {
                    $errorMessages[] = "Pseudo ou email déjà utilisé";
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $age = floor((time() - strtotime($birthdate)) / (365.25 * 24 * 3600));
                    
                    if ($this->userModel->create([
                        'username' => $username,
                        'email' => $email,
                        'password_hash' => $hash,
                        'phone' => $phone ?: null,
                        'age' => $age,
                        'birthdate' => $birthdate,
                        'profile_picture' => 'assets/user_icon.png'
                    ])) {
                        $_SESSION['user_id'] = $this->db->lastInsertId();
                        $_SESSION['username'] = $username;
                        $this->redirect('/netchat/public/');
                    } else {
                        $errorMessages[] = "Erreur lors de l'inscription";
                    }
                }
            } else {
                $errorMessages = $errors;
            }
        }
        
        $this->view('auth/register', ['errorMessages' => $errorMessages, 'success' => $success]);
    }
    
    public function logout() {
        session_destroy();
        // Redirect to public login page
        $this->redirect('/netchat/public/login');
    }
    
    private function validateRegistration($username, $email, $password, $confirm_password, $phone, $birthdate) {
        $errors = [];
        
        if ($password !== $confirm_password) {
            $errors[] = "Les mots de passe ne correspondent pas";
        }
        if (strlen($username) < 3 || strlen($username) > 20 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = "Pseudo invalide (3-20 caractères, lettres/chiffres/_)";
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Email invalide";
        }
        if (strlen($password) < 8) {
            $errors[] = "Mot de passe trop court (8 caractères minimum)";
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Le mot de passe doit contenir au moins une majuscule";
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Le mot de passe doit contenir au moins une minuscule";
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Le mot de passe doit contenir au moins un chiffre";
        }
        if (!preg_match('/[\W_]/', $password)) {
            $errors[] = "Le mot de passe doit contenir au moins un caractère spécial";
        }
        if (stripos($password, $username) !== false) {
            $errors[] = "Le mot de passe ne doit pas contenir votre pseudo";
        }
        if (empty($birthdate)) {
            $errors[] = "La date de naissance est obligatoire";
        } elseif (strtotime($birthdate) > strtotime('-13 years')) {
            $errors[] = "Vous devez avoir au moins 13 ans";
        }
        if (!empty($phone) && !preg_match('/^[\+]?[0-9\s\-\(\)]{10,15}$/', $phone)) {
            $errors[] = "Numéro de téléphone invalide";
        }
        
        return $errors;
    }
}
