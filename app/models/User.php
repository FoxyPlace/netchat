<?php
/**
 * Model User - Gestion des utilisateurs
 */
class User {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function findByUsername($username) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function findByUsernameOrEmail($identifier) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$identifier, $identifier]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO users (username, email, password_hash, phone, age, birthdate, profile_picture) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['username'],
            $data['email'],
            $data['password_hash'],
            $data['phone'] ?? null,
            $data['age'] ?? null,
            $data['birthdate'] ?? null,
            $data['profile_picture'] ?? 'assets/user_icon.png'
        ]);
    }
    
    public function update($id, $data) {
        $fields = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
        
        $values[] = $id;
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }
    
    public function updatePassword($id, $password_hash) {
        $stmt = $this->db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        return $stmt->execute([$password_hash, $id]);
    }
    
    public function usernameExists($username, $excludeId = null) {
        $sql = "SELECT id FROM users WHERE username = ?";
        $params = [$username];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }
    
    public function emailExists($email, $excludeId = null) {
        $sql = "SELECT id FROM users WHERE email = ?";
        $params = [$email];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    /**
     * Récupère tous les utilisateurs avec pagination
     */
    public function findAll($offset = 0, $limit = 50) {
        $limit = (int)$limit;
        $offset = (int)$offset;
        if ($limit < 1) $limit = 1;
        if ($limit > 100) $limit = 100;

        $stmt = $this->db->prepare("
            SELECT id, username, email, profile_picture, account_type, admin, created_at, last_login, is_verified, status
            FROM users 
            ORDER BY created_at DESC
            LIMIT " . $limit . " OFFSET " . $offset . "
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Compte le nombre total d'utilisateurs
     */
    public function countAll() {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM users");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'];
    }

    /**
     * Bannir un utilisateur
     */
    public function banUser($userId, $banReason = '') {
        // Générer un nouveau username unique pour "Utilisateur Banni"
        $bannedUsername = 'Utilisateur Banni ' . $userId;
        
        // Vérifier si le username existe déjà
        $counter = 1;
        $originalBannedUsername = $bannedUsername;
        while ($this->usernameExists($bannedUsername, $userId)) {
            $bannedUsername = $originalBannedUsername . '_' . $counter;
            $counter++;
        }

        $stmt = $this->db->prepare("
            UPDATE users 
            SET username = ?, 
                profile_picture = 'assets/user_icon.png',
                bio = ?
            WHERE id = ?
        ");
        return $stmt->execute([$bannedUsername, $banReason, $userId]);
    }

    /**
     * Vérifie si un utilisateur est banni
     */
    public function isBanned($userId) {
        $user = $this->findById($userId);
        return $user && strpos($user['username'], 'Utilisateur Banni') === 0;
    }

    /**
     * Met à jour le type de compte (grade)
     */
    public function updateAccountType($userId, $accountType) {
        $validTypes = ['user', 'moderator', 'administrator'];
        if (!in_array($accountType, $validTypes)) {
            return false;
        }

        $stmt = $this->db->prepare("UPDATE users SET account_type = ? WHERE id = ?");
        return $stmt->execute([$accountType, $userId]);
    }

    /**
     * Récupère les informations détaillées d'un utilisateur pour l'admin
     */
    public function findForAdmin($userId) {
        $stmt = $this->db->prepare("
            SELECT u.*, 
                   (SELECT COUNT(*) FROM posts WHERE user_id = u.id) as posts_count,
                   (SELECT COUNT(*) FROM follows WHERE following_id = u.id) as followers_count,
                   (SELECT COUNT(*) FROM follows WHERE follower_id = u.id) as following_count,
                   (SELECT COUNT(*) FROM report WHERE reported_user_id = u.id) as reports_count
            FROM users u 
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $user['is_banned'] = strpos($user['username'], 'Utilisateur Banni') === 0;
            $user['posts_count'] = (int)$user['posts_count'];
            $user['followers_count'] = (int)$user['followers_count'];
            $user['following_count'] = (int)$user['following_count'];
            $user['reports_count'] = (int)$user['reports_count'];
        }
        
        return $user;
    }
}
