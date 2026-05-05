<?php
/**
 * Model Message - gestion des messages privés
 */
class Message {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function send($senderId, $receiverId, $content) {
        $stmt = $this->db->prepare("INSERT INTO messages (sender_id, receiver_id, content, is_read) VALUES (?, ?, ?, 0)");
        $ok = $stmt->execute([(int)$senderId, (int)$receiverId, (string)$content]);
        if (!$ok) return false;
        return (int)$this->db->lastInsertId();
    }

    public function getConversationList($userId, $limit = 20, $offset = 0) {
        $limit = (int)$limit;
        $offset = (int)$offset;
        if ($limit < 1) $limit = 1;
        if ($limit > 100) $limit = 100;

        // Last message per "other user" + unread count
        $sql = "
            SELECT 
                t.other_user_id,
                CASE WHEN t.other_user_id = 0 THEN 'NETCHAT' ELSE u.username END AS username,
                CASE WHEN t.other_user_id = 0 THEN 'assets/logo.png' ELSE u.profile_picture END AS profile_picture,
                t.last_message,
                t.last_at,
                COALESCE(unread.unread_count, 0) AS unread_count
            FROM (
                SELECT 
                    CASE WHEN m.sender_id = :uid THEN m.receiver_id ELSE m.sender_id END AS other_user_id,
                    MAX(m.created_at) AS last_at
                FROM messages m
                WHERE m.sender_id = :uid OR m.receiver_id = :uid
                GROUP BY other_user_id
                ORDER BY last_at DESC
                LIMIT " . $limit . " OFFSET " . $offset . "
            ) x
            JOIN (
                SELECT 
                    CASE WHEN m.sender_id = :uid THEN m.receiver_id ELSE m.sender_id END AS other_user_id,
                    m.content AS last_message,
                    m.created_at AS last_at
                FROM messages m
                WHERE (m.sender_id = :uid OR m.receiver_id = :uid)
            ) t ON t.other_user_id = x.other_user_id AND t.last_at = x.last_at
            LEFT JOIN users u ON u.id = t.other_user_id AND t.other_user_id != 0
            LEFT JOIN (
                SELECT sender_id AS other_user_id, COUNT(*) AS unread_count
                FROM messages
                WHERE receiver_id = :uid AND is_read = 0
                GROUP BY sender_id
            ) unread ON unread.other_user_id = t.other_user_id
            ORDER BY t.last_at DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['uid' => (int)$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMessagesBetween($userId, $otherUserId, $limit = 50, $beforeId = null) {
        $limit = (int)$limit;
        if ($limit < 1) $limit = 1;
        if ($limit > 200) $limit = 200;

        $params = [(int)$userId, (int)$otherUserId, (int)$otherUserId, (int)$userId];
        $extra = "";
        if ($beforeId !== null) {
            $extra = " AND m.id < ? ";
            $params[] = (int)$beforeId;
        }

        $stmt = $this->db->prepare("
            SELECT m.*,
                   CASE WHEN m.sender_id = 0 THEN 'NETCHAT' ELSE su.username END AS sender_username,
                   CASE WHEN m.sender_id = 0 THEN 'assets/logo.png' ELSE su.profile_picture END AS sender_profile_picture
            FROM messages m
            LEFT JOIN users su ON su.id = m.sender_id AND m.sender_id != 0
            WHERE ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = 0 AND m.receiver_id = ?))
            $extra
            ORDER BY m.id DESC
            LIMIT " . $limit . "
        ");
        $stmt->execute(array_merge($params, [(int)$userId]));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // return ascending
        return array_reverse($rows);
    }

    public function markReadFromSender($receiverId, $senderId) {
        $stmt = $this->db->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND sender_id = ? AND is_read = 0");
        return $stmt->execute([(int)$receiverId, (int)$senderId]);
    }

    /**
     * Créer un message (supporte les messages système avec sender_id = 0)
     */
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO messages (sender_id, receiver_id, content, is_read) 
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([
            (int)$data['sender_id'],
            (int)$data['receiver_id'],
            (string)$data['content'],
            (int)($data['is_read'] ?? 0)
        ]);
    }
}

