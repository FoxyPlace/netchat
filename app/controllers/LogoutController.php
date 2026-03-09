<?php
require_once __DIR__ . '/../../core/BaseController.php';
require_once __DIR__ . '/../models/Logout.php';

class LogoutController extends BaseController {
    private $logoutModel;

    public function __construct() {
        parent::__construct();
        $this->logoutModel = new Logout($this->db);
    }

    public function logout() {
        // Si utilisateur connecté, enregistrer last_login via le model
        if (isset($_SESSION['user_id'])) {
            // On ne bloque pas la déconnexion si l'enregistrement échoue
            $this->logoutModel->recordLastLogin((int)$_SESSION['user_id']);
        }

        // Détruire la session puis rediriger vers la page de login
        session_destroy();
        $this->redirect('/login');
    }
}
