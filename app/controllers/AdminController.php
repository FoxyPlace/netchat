<?php
require_once __DIR__ . '/../../core/BaseController.php';
require_once __DIR__ . '/../models/User.php';

class AdminController extends BaseController {
    private $userModel;

    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->userModel = new User($this->db);
    }

    public function index() {
        $user = $this->userModel->findById($_SESSION['user_id']);
        $isAdmin = false;
        if ($user) {
            if (!empty($user['account_type']) && in_array($user['account_type'], ['administrator', 'moderator'], true)) {
                $isAdmin = true;
            } elseif (!empty($user['admin']) && (int)$user['admin'] === 1) {
                $isAdmin = true;
            }
        }

        if (!$user || !$isAdmin) {
            // Rediriger les non-admins vers l'accueil
            $this->redirect('/');
        }

        $this->view('admin/panel', [
            'user' => $user
        ]);
    }
}
