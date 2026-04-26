<?php
require_once __DIR__ . '/../../core/BaseController.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Message.php';

class ChatController extends BaseController {
    private $userModel;
    private $messageModel;

    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->userModel = new User($this->db);
        $this->messageModel = new Message($this->db);
    }

    public function index() {
        $user = $this->userModel->findById($_SESSION['user_id']);
        if (!$user) {
            $this->redirect('/login');
        }

        $user_profile_picture = $user['profile_picture'] ?? 'assets/user_icon.png';
        $user_profile_picture_path = __DIR__ . '/../../public/' . $user_profile_picture;
        if (!file_exists($user_profile_picture_path)) {
            $user_profile_picture = 'assets/user_icon.png';
        }

        $openUserId = isset($_GET['user']) ? (int)$_GET['user'] : 0;
        if ($openUserId === (int)$_SESSION['user_id']) {
            $openUserId = 0;
        }
        $openUser = $openUserId > 0 ? $this->userModel->findById($openUserId) : null;

        $this->view('chat/index', [
            'user' => $user,
            'user_profile_picture' => $user_profile_picture,
            'open_user' => $openUser,
            'open_user_id' => $openUserId
        ]);
    }
}

