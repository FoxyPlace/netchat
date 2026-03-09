<?php
/**
 * Model Logout - opérations liées à la déconnexion (p. ex. enregistrer last_login)
 */
class Logout {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Enregistre la date/heure de dernière connexion pour un utilisateur.
     * Retourne true en cas de succès, false sinon.
     */
    public function recordLastLogin($userId) {
        if (empty($userId)) {
            return false;
        }

        $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        return $stmt->execute([$userId]);
    }
}
