<?php
require_once __DIR__ . '/../../core/BaseController.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Post.php';

class ProfileController extends BaseController {
    private $userModel;
    private $postModel;
    
    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->userModel = new User($this->db);
        $this->postModel = new Post($this->db);
    }
    
    public function show() {
        $profile_user_id = isset($_GET['id']) ? (int)$_GET['id'] : $_SESSION['user_id'];
        
        $profile_user = $this->userModel->findById($profile_user_id);
        if (!$profile_user) {
            $this->redirect('/dashboard');
        }
        
        $posts = $this->postModel->findByUserId($profile_user_id);
        
        $profile_picture = $profile_user['profile_picture'] ?? 'assets/user_icon.png';
        $profile_picture_path = __DIR__ . '/../../public/' . $profile_picture;
        if (!file_exists($profile_picture_path)) {
            $profile_picture = 'assets/user_icon.png';
        }
        
        // Infos utilisateur connecté pour sidebar
        $current_user = $this->userModel->findById($_SESSION['user_id']);
        $user_profile_picture = $current_user['profile_picture'] ?? 'assets/user_icon.png';
        $user_profile_picture_path = __DIR__ . '/../../public/' . $user_profile_picture;
        if (!file_exists($user_profile_picture_path)) {
            $user_profile_picture = 'assets/user_icon.png';
        }
        
        // Déterminer si l'utilisateur courant suit ce profil
        require_once __DIR__ . '/../models/Follow.php';
        $followModel = new Follow($this->db);
        $isFollowing = $followModel->isFollowing($_SESSION['user_id'], $profile_user_id);

    // Statut ami / demande
    require_once __DIR__ . '/../models/Friend.php';
    $friendModel = new Friend($this->db);
    $isFriend = $friendModel->areFriends($_SESSION['user_id'], $profile_user_id);
    $outgoingRequest = $friendModel->getStatus($_SESSION['user_id'], $profile_user_id) === 'pending';
    $incomingRequest = $friendModel->getStatus($profile_user_id, $_SESSION['user_id']) === 'pending';

        $this->view('profile/show', [
            'profile_user' => $profile_user,
            'profile_user_id' => $profile_user_id,
            'posts' => $posts,
            'profile_picture' => $profile_picture,
            'current_user' => $current_user,
            'user_profile_picture' => $user_profile_picture
            ,'isFollowing' => $isFollowing
            ,'isFriend' => $isFriend
            ,'outgoingRequest' => $outgoingRequest
            ,'incomingRequest' => $incomingRequest
        ]);
    }
}
