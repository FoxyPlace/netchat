<?php
/**
 * Model Notification - gestion des notifications
 */
class Notification {
    private $db;

    public function __construct($db) {
        $this->db = $db;
        $this->ensureSchema();
    }

    private function ensureSchema() {
        // Étendre l'ENUM `notifications.type` si besoin (best-effort).
        // Sans ça, insérer 'like'/'comment'/'message_request' peut déclencher une exception SQL.
        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM notifications LIKE 'type'");
            $col = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
            if (!$col || empty($col['Type'])) return;
            if (stripos($col['Type'], 'enum(') !== 0) return;

            preg_match_all("/'([^']+)'/", $col['Type'], $m);
            $existing = $m[1] ?? [];
            if (empty($existing)) return;

            $required = ['friend_request', 'friend_accept', 'follow', 'mention', 'message', 'system', 'like', 'comment', 'message_request'];
            $toAdd = array_values(array_diff($required, $existing));
            if (empty($toAdd)) return;

            $merged = $existing;
            foreach ($required as $t) {
                if (!in_array($t, $merged, true)) $merged[] = $t;
            }

            $enumSql = "ALTER TABLE notifications MODIFY type ENUM(" .
                implode(',', array_map(fn($v) => $this->db->quote($v), $merged)) .
                ") COLLATE utf8mb4_unicode_ci NOT NULL";
            $this->db->exec($enumSql);
        } catch (Exception $e) {
            // ignore
        }
    }

    public function create($userId, $type, $actorId = null, $targetId = null, $data = null) {
        $json = null;
        if ($data !== null) {
            $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        }

        $stmt = $this->db->prepare("
            INSERT INTO notifications (user_id, type, actor_id, target_id, data, is_read)
            VALUES (?, ?, ?, ?, ?, 0)
        ");
        return $stmt->execute([(int)$userId, (string)$type, $actorId !== null ? (int)$actorId : null, $targetId !== null ? (int)$targetId : null, $json]);
    }

    public function listByUser($userId, $limit = 30, $offset = 0) {
        $limit = (int)$limit;
        $offset = (int)$offset;
        if ($limit < 1) $limit = 1;
        if ($limit > 100) $limit = 100;

        $stmt = $this->db->prepare("
            SELECT n.*,
                   u.username AS actor_username,
                   u.profile_picture AS actor_profile_picture
            FROM notifications n
            LEFT JOIN users u ON n.actor_id = u.id
            WHERE n.user_id = ?
            ORDER BY n.created_at DESC
            LIMIT " . $limit . " OFFSET " . $offset . "
        ");
        $stmt->execute([(int)$userId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($items as &$it) {
            if (isset($it['data']) && $it['data'] !== null && $it['data'] !== '') {
                $decoded = json_decode($it['data'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $it['data'] = $decoded;
                }
            }
            $it['is_read'] = (bool)$it['is_read'];
        }
        return $items;
    }

    public function countUnread($userId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS c FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([(int)$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['c'] ?? 0);
    }

    public function markRead($userId, $notificationId) {
        $stmt = $this->db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        return $stmt->execute([(int)$notificationId, (int)$userId]);
    }

    public function markAllRead($userId) {
        $stmt = $this->db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        return $stmt->execute([(int)$userId]);
    }

    public function deleteById($userId, $notificationId) {
        $stmt = $this->db->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
        return $stmt->execute([(int)$notificationId, (int)$userId]);
    }

    public function deleteAllForUser($userId) {
        $stmt = $this->db->prepare("DELETE FROM notifications WHERE user_id = ?");
        return $stmt->execute([(int)$userId]);
    }
}

