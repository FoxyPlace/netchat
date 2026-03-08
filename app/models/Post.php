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
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        return $stmt->execute([$userId, $content, $imageUrl]);
    }
    
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM posts WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
