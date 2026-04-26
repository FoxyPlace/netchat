<?php
require_once __DIR__ . '/../../core/BaseController.php';
require_once __DIR__ . '/../models/Comment.php';
require_once __DIR__ . '/../models/Notification.php';

class CommentController extends BaseController {
    private $commentModel;
    private $notificationModel;

    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->commentModel = new Comment($this->db);
        $this->notificationModel = new Notification($this->db);
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
        if ($res) {
            // Notification au propriétaire du post
            try {
                $q = $this->db->prepare("SELECT user_id, content FROM posts WHERE id = ?");
                $q->execute([$postId]);
                $p = $q->fetch(PDO::FETCH_ASSOC);
                if ($p) {
                    $ownerId = (int)$p['user_id'];
                    if ($ownerId && $ownerId !== $userId) {
                        $excerpt = mb_substr($content, 0, 120);
                        $this->notificationModel->create($ownerId, 'comment', $userId, $postId, ['post_id' => $postId, 'excerpt' => $excerpt]);
                    }
                }
            } catch (Exception $e) {
                // ignore
            }
            echo json_encode(['success' => true, 'comment' => $res]);
        }
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
