<?php
/**
 * Model Post - Gestion des posts
 */
class Post {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function findAll($offset = 0, $limit = 10) {
        $limit = (int)$limit;
        $offset = (int)$offset;
        $stmt = $this->db->prepare("
            SELECT p.*, 
                   u.username, 
                   u.profile_picture,
                   COUNT(DISTINCT r.id) as likes
            FROM posts p
            INNER JOIN users u ON p.user_id = u.id
            LEFT JOIN reactions r ON p.id = r.post_id AND r.reaction_type = 'like'
            GROUP BY p.id
            ORDER BY p.created_at DESC
            LIMIT " . $limit . " OFFSET " . $offset . "
        ");
        $stmt->execute();
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Pour chaque post, récupérer les mentions associées
        $mentionStmt = $this->db->prepare("SELECT pm.mentioned_user_id, u.username, pm.mention_position FROM post_mentions pm JOIN users u ON pm.mentioned_user_id = u.id WHERE pm.post_id = ?");
        foreach ($posts as &$post) {
            $mentionStmt->execute([$post['id']]);
            $post['mentions'] = $mentionStmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $posts;
    }
    
    public function findByUserId($userId, $limit = 20) {
        $limit = (int)$limit; // S'assurer que c'est un entier
        $stmt = $this->db->prepare("
            SELECT p.*, 
                   COUNT(DISTINCT r.id) as likes
            FROM posts p
            LEFT JOIN reactions r ON p.id = r.post_id AND r.reaction_type = 'like'
            WHERE p.user_id = ?
            GROUP BY p.id
            ORDER BY p.created_at DESC
            LIMIT " . $limit . "
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function create($userId, $content, $imageUrl = null) {
        $stmt = $this->db->prepare("
            INSERT INTO posts (user_id, content, image_url) 
            VALUES (?, ?, ?)
        ");
            $ok = $stmt->execute([$userId, $content, $imageUrl]);

            if ($ok) {
                $postId = (int)$this->db->lastInsertId();
                // Traiter les mentions côté serveur
                $mentions = $this->extractMentions($content);
                if (!empty($mentions)) {
                    $insert = $this->db->prepare("INSERT IGNORE INTO post_mentions (post_id, mentioned_user_id, mention_position) VALUES (?, ?, ?)");
                    foreach ($mentions as $username) {
                        // Chercher l'utilisateur mentionné
                        $u = $this->db->prepare("SELECT id FROM users WHERE username = ?");
                        $u->execute([$username]);
                        $row = $u->fetch(PDO::FETCH_ASSOC);
                        if ($row) {
                            $mentionedId = (int)$row['id'];
                            $position = mb_strpos($content, '@' . $username);
                            if ($position === false) $position = null;
                            $insert->execute([$postId, $mentionedId, $position]);
                        }
                    }
                }

                return $postId;
            }

            return false;
    }
    
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM posts WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Extraire les mentions @username depuis un texte
     * Retourne un tableau de usernames (unique)
     */
    private function extractMentions($content) {
        $matches = [];
        preg_match_all('/(?<=\s|^)@([A-Za-z0-9_]{1,50})\b/u', $content, $matches);
        if (!empty($matches[1])) {
            $usernames = array_unique($matches[1]);
            return array_values($usernames);
        }
        return [];
    }
}
