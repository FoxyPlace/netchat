<?php
require_once __DIR__ . '/../../core/BaseController.php';
require_once __DIR__ . '/../models/User.php';

class PasswordController extends BaseController {
    private $userModel;
    
    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->userModel = new User($this->db);
    }
    
    public function edit() {
        $user = $this->userModel->findById($_SESSION['user_id']);
        $errorMessages = [];
        $successMessage = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            $errors = $this->validatePassword($new_password, $confirm_password, $user['username']);
            
            if (empty($errors)) {
                $hash = password_hash($new_password, PASSWORD_DEFAULT);
                if ($this->userModel->updatePassword($_SESSION['user_id'], $hash)) {
                    $successMessage = "Mot de passe mis à jour avec succès";
                } else {
                    $errorMessages[] = "Erreur lors de la mise à jour du mot de passe";
                }
            } else {
                $errorMessages = $errors;
            }
        }
        
        // Photo de profil pour la sidebar
        $profile_picture = $user['profile_picture'] ?? 'assets/user_icon.png';
        $profile_picture_path = __DIR__ . '/../../public/' . $profile_picture;
        if (!file_exists($profile_picture_path)) {
            $profile_picture = 'assets/user_icon.png';
        }
        
        $this->view('password/edit', [
            'user' => $user,
            'profile_picture' => $profile_picture,
            'errorMessages' => $errorMessages,
            'successMessage' => $successMessage
        ]);
    }
    
    private function validatePassword($password, $confirm_password, $username) {
        $errors = [];
        
        if ($password !== $confirm_password) {
            $errors[] = "Les mots de passe ne correspondent pas";
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
        
        return $errors;
    }
}
