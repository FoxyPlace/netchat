<?php
/**
 * Model Follow - gestion des abonnements
 */
class Follow {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function isFollowing($userId, $targetId) {
        $stmt = $this->db->prepare("SELECT id FROM friendships WHERE user_id = ? AND friend_id = ? AND status = 'accepted'");
        $stmt->execute([$userId, $targetId]);
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function follow($userId, $targetId) {
        try {
            $this->db->beginTransaction();
            // Insert or update to accepted
            $stmt = $this->db->prepare("INSERT INTO friendships (user_id, friend_id, status) VALUES (?, ?, 'accepted') ON DUPLICATE KEY UPDATE status = 'accepted'");
            $stmt->execute([$userId, $targetId]);

            // Increment followers_count of target
            $upd = $this->db->prepare("UPDATE users SET followers_count = followers_count + 1 WHERE id = ?");
            $upd->execute([$targetId]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function unfollow($userId, $targetId) {
        try {
            $this->db->beginTransaction();
            $del = $this->db->prepare("DELETE FROM friendships WHERE user_id = ? AND friend_id = ?");
            $del->execute([$userId, $targetId]);

            // Decrement followers_count but not below 0
            $upd = $this->db->prepare("UPDATE users SET followers_count = GREATEST(followers_count - 1, 0) WHERE id = ?");
            $upd->execute([$targetId]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function getFollowersCount($userId) {
        $stmt = $this->db->prepare("SELECT followers_count FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['followers_count'] : 0;
    }
}
