<?php
require_once __DIR__ . '/../../core/BaseController.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Post.php';

class DashboardController extends BaseController {
    private $userModel;
    private $postModel;
    
    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->userModel = new User($this->db);
        $this->postModel = new Post($this->db);
    }
    
    public function index() {
        $user = $this->userModel->findById($_SESSION['user_id']);
        $user_profile_picture = $user['profile_picture'] ?? 'assets/user_icon.png';
        $user_profile_picture_path = __DIR__ . '/../../public/' . $user_profile_picture;
        if (!file_exists($user_profile_picture_path)) {
            $user_profile_picture = 'assets/user_icon.png';
        }
        
        $this->view('dashboard/index', [
            'user' => $user,
            'user_profile_picture' => $user_profile_picture
        ]);
    }
}
