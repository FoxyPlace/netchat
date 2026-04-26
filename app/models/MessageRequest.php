<?php
/**
 * Model MessageRequest - demandes de message (style TikTok)
 */
class MessageRequest {
    private $db;

    public function __construct($db) {
        $this->db = $db;
        $this->ensureTable();
    }

    private function ensureTable() {
        // Création lazy pour éviter une migration manuelle
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS message_requests (
                id INT NOT NULL AUTO_INCREMENT,
                requester_id INT NOT NULL,
                target_id INT NOT NULL,
                status ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_request (requester_id, target_id),
                KEY idx_target_status (target_id, status),
                KEY idx_requester (requester_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }

    public function getStatus($requesterId, $targetId) {
        $stmt = $this->db->prepare("SELECT status FROM message_requests WHERE requester_id = ? AND target_id = ?");
        $stmt->execute([(int)$requesterId, (int)$targetId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (string)$row['status'] : null;
    }

    public function ensurePending($requesterId, $targetId) {
        $stmt = $this->db->prepare("
            INSERT INTO message_requests (requester_id, target_id, status)
            VALUES (?, ?, 'pending')
            ON DUPLICATE KEY UPDATE status = IF(status = 'accepted', 'accepted', 'pending')
        ");
        return $stmt->execute([(int)$requesterId, (int)$targetId]);
    }

    public function accept($requesterId, $targetId) {
        $stmt = $this->db->prepare("
            INSERT INTO message_requests (requester_id, target_id, status)
            VALUES (?, ?, 'accepted')
            ON DUPLICATE KEY UPDATE status = 'accepted'
        ");
        return $stmt->execute([(int)$requesterId, (int)$targetId]);
    }

    public function canChat($a, $b) {
        $a = (int)$a; $b = (int)$b;
        if ($a <= 0 || $b <= 0 || $a === $b) return false;

        // Follow mutuel
        $stmt = $this->db->prepare("
            SELECT 1
            FROM follows f1
            JOIN follows f2 ON f2.follower_id = f1.following_id AND f2.following_id = f1.follower_id
            WHERE f1.follower_id = ? AND f1.following_id = ?
            LIMIT 1
        ");
        $stmt->execute([$a, $b]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)) return true;

        // Sinon, demande acceptée dans un sens ou l'autre
        $q = $this->db->prepare("
            SELECT 1 FROM message_requests
            WHERE status = 'accepted'
              AND ((requester_id = ? AND target_id = ?) OR (requester_id = ? AND target_id = ?))
            LIMIT 1
        ");
        $q->execute([$a, $b, $b, $a]);
        return (bool)$q->fetch(PDO::FETCH_ASSOC);
    }

    public function listPendingForTarget($targetId, $limit = 30) {
        $limit = (int)$limit;
        if ($limit < 1) $limit = 1;
        if ($limit > 100) $limit = 100;
        $stmt = $this->db->prepare("
            SELECT mr.*, u.username, u.profile_picture
            FROM message_requests mr
            JOIN users u ON u.id = mr.requester_id
            WHERE mr.target_id = ? AND mr.status = 'pending'
            ORDER BY mr.created_at DESC
            LIMIT " . $limit . "
        ");
        $stmt->execute([(int)$targetId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

