<?php
/**
 * Model Comment - gestion des commentaires
 */
class Comment {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function create($userId, $postId, $content) {
        $stmt = $this->db->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
        $ok = $stmt->execute([$postId, $userId, $content]);
        if (!$ok) return false;
        $commentId = (int)$this->db->lastInsertId();

        // notification au propriétaire du post (commentaire)
        try {
            if (class_exists('Notification')) {
                $q = $this->db->prepare("SELECT user_id, content FROM posts WHERE id = ?");
                $q->execute([(int)$postId]);
                $p = $q->fetch(PDO::FETCH_ASSOC);
                if ($p) {
                    $ownerId = (int)$p['user_id'];
                    if ($ownerId && $ownerId !== (int)$userId) {
                        $notif = new Notification($this->db);
                        $excerpt = mb_substr(strip_tags((string)$content), 0, 120);
                        $notif->create($ownerId, 'comment', (int)$userId, (int)$postId, ['post_id' => (int)$postId, 'comment_id' => (int)$commentId, 'excerpt' => $excerpt]);
                    }
                }
            }
        } catch (Exception $e) {
            // ignore
        }

        // handle mentions in comment
        $mentions = $this->extractMentions($content);
        if (!empty($mentions)) {
            $insert = $this->db->prepare("INSERT IGNORE INTO comment_mentions (comment_id, mentioned_user_id) VALUES (?, ?)");
            foreach ($mentions as $username) {
                $u = $this->db->prepare("SELECT id FROM users WHERE username = ?");
                $u->execute([$username]);
                $row = $u->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $mentionedId = (int)$row['id'];
                    $insert->execute([$commentId, $mentionedId]);
                    // create notification for mention
                    if (class_exists('Notification')) {
                        $notif = new Notification($this->db);
                        $excerpt = mb_substr(strip_tags($content), 0, 120);
                        $notif->create($mentionedId, 'mention', $userId, null, ['comment_id' => $commentId, 'excerpt' => $excerpt]);
                    }
                }
            }
        }

        // return created comment with author info
        $q = $this->db->prepare("SELECT c.*, u.username, u.profile_picture FROM comments c JOIN users u ON c.user_id = u.id WHERE c.id = ?");
        $q->execute([$commentId]);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        return $row ? $row : false;
    }

    public function deleteById($commentId, $userId) {
        // verify ownership
        $stmt = $this->db->prepare("SELECT user_id FROM comments WHERE id = ?");
        $stmt->execute([$commentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return ['error' => 'Commentaire introuvable'];
        if ((int)$row['user_id'] !== (int)$userId) return ['error' => 'Pas autorisé'];

        // delete mentions for this comment
        $delMentions = $this->db->prepare("DELETE FROM comment_mentions WHERE comment_id = ?");
        $delMentions->execute([$commentId]);

        // delete comment
        $del = $this->db->prepare("DELETE FROM comments WHERE id = ?");
        $del->execute([$commentId]);
        return ['success' => true];
    }

    public function findByPost($postId) {
        $stmt = $this->db->prepare("SELECT c.*, u.username, u.profile_picture FROM comments c JOIN users u ON c.user_id = u.id WHERE c.post_id = ? ORDER BY c.created_at ASC");
        $stmt->execute([$postId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function extractMentions($content) {
        $matches = [];
        preg_match_all('/(?<=\s|^)@([A-Za-z0-9_]{1,50})\b/u', $content, $matches);
        if (!empty($matches[1])) return array_values(array_unique($matches[1]));
        return [];
    }
}
