<?php
require_once __DIR__ . '/../../core/BaseController.php';
require_once __DIR__ . '/../models/Comment.php';

class CommentController extends BaseController {
    private $commentModel;

    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->commentModel = new Comment($this->db);
    }

    // POST /comment/create
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }
        $userId = (int)$_SESSION['user_id'];
        $postId = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
        $content = trim($_POST['content'] ?? '');
        header('Content-Type: application/json');
        if ($postId <= 0 || $content === '') {
            echo json_encode(['error' => 'Paramètres invalides']);
            return;
        }
        $res = $this->commentModel->create($userId, $postId, $content);
        if ($res) echo json_encode(['success' => true, 'comment' => $res]);
        else echo json_encode(['error' => 'Erreur création commentaire']);
    }

    // POST /comment/delete
    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }
        $userId = (int)$_SESSION['user_id'];
        $commentId = isset($_POST['comment_id']) ? (int)$_POST['comment_id'] : 0;
        header('Content-Type: application/json');
        if ($commentId <= 0) { echo json_encode(['error' => 'Commentaire invalide']); return; }
        $res = $this->commentModel->deleteById($commentId, $userId);
        echo json_encode($res);
    }
}
