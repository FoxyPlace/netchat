<?php
require_once __DIR__ . '/../../core/BaseController.php';
require_once __DIR__ . '/../models/User.php';

class SettingsController extends BaseController {
    private $userModel;
    
    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->userModel = new User($this->db);
    }
    
    public function index() {
        $user = $this->userModel->findById($_SESSION['user_id']);
        if (!$user) {
            $this->redirect('/login');
        }
        
        $errorMessages = [];
        $successMessage = '';
        
        // Traitement des formulaires
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            
            if ($action === 'update_profile') {
                $result = $this->updateProfile($user);
                $errorMessages = $result['errors'] ?? [];
                $successMessage = $result['success'] ?? '';
                if (empty($errorMessages)) {
                    $user = $this->userModel->findById($_SESSION['user_id']); // Recharger
                }
            } elseif ($action === 'update_account') {
                $result = $this->updateAccount($user);
                $errorMessages = $result['errors'] ?? [];
                $successMessage = $result['success'] ?? '';
                if (empty($errorMessages)) {
                    $user = $this->userModel->findById($_SESSION['user_id']); // Recharger
                }
            }
        }
        
        $profile_picture = $user['profile_picture'] ?? 'assets/user_icon.png';
        $profile_picture_path = __DIR__ . '/../../public/' . $profile_picture;
        if (!file_exists($profile_picture_path)) {
            $profile_picture = 'assets/user_icon.png';
        }
        
        $this->view('settings/index', [
            'user' => $user,
            'profile_picture' => $profile_picture,
            'errorMessages' => $errorMessages,
            'successMessage' => $successMessage
        ]);
    }
    
    private function updateProfile($user) {
        // Logique de mise à jour du profil (à extraire de settingsprofile.php)
        // Pour l'instant, retour vide
        return ['errors' => [], 'success' => ''];
    }
    
    private function updateAccount($user) {
        // Logique de mise à jour du compte (à extraire de settingsprofile.php)
        // Pour l'instant, retour vide
        return ['errors' => [], 'success' => ''];
    }
}
