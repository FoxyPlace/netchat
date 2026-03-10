<?php
/**
 * Model Friend - gestion des demandes d'amis
 */
class Friend {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Retourne le statut de la relation requester -> target si existe (pending|accepted) ou null
    public function getStatus($requesterId, $targetId) {
        $stmt = $this->db->prepare("SELECT status FROM friendships WHERE user_id = ? AND friend_id = ? LIMIT 1");
        $stmt->execute([$requesterId, $targetId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['status'] : null;
    }

    // Envoyer une demande (ou annuler si existe pending)
    public function toggleRequest($userId, $targetId) {
        try {
            // Vérifier existence
            $stmt = $this->db->prepare("SELECT id, status FROM friendships WHERE user_id = ? AND friend_id = ?");
            $stmt->execute([$userId, $targetId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                if ($row['status'] === 'pending') {
                    // annuler la demande
                    $del = $this->db->prepare("DELETE FROM friendships WHERE id = ?");
                    $del->execute([$row['id']]);
                    return ['requested' => false];
                } elseif ($row['status'] === 'accepted') {
                    // retirer l'amitié (optionnel)
                    $del = $this->db->prepare("DELETE FROM friendships WHERE id = ?");
                    $del->execute([$row['id']]);
                    // also delete reciprocal
                    $del2 = $this->db->prepare("DELETE FROM friendships WHERE user_id = ? AND friend_id = ?");
                    $del2->execute([$targetId, $userId]);
                    return ['requested' => false, 'removed' => true];
                }
            } else {
                // créer une demande en attente
                $ins = $this->db->prepare("INSERT INTO friendships (user_id, friend_id, status) VALUES (?, ?, 'pending')");
                $ins->execute([$userId, $targetId]);
                return ['requested' => true];
            }

            return ['requested' => false];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    // Accepter une demande (requester -> currentUser)
    public function acceptRequest($requesterId, $currentUserId) {
        try {
            $this->db->beginTransaction();
            // Mettre à accepted la ligne existante
            $upd = $this->db->prepare("UPDATE friendships SET status = 'accepted' WHERE user_id = ? AND friend_id = ? AND status = 'pending'");
            $upd->execute([$requesterId, $currentUserId]);

            // Insérer la ligne réciproque accepted si absente
            $ins = $this->db->prepare("INSERT IGNORE INTO friendships (user_id, friend_id, status) VALUES (?, ?, 'accepted')");
            $ins->execute([$currentUserId, $requesterId]);

            $this->db->commit();
            return ['accepted' => true];
        } catch (Exception $e) {
            if ($this->db && $this->db->inTransaction()) $this->db->rollBack();
            return ['error' => $e->getMessage()];
        }
    }

    // Vérifier si deux utilisateurs sont amis (accepted) — cherche dans les deux sens
    public function areFriends($a, $b) {
        $stmt = $this->db->prepare("SELECT 1 FROM friendships WHERE ((user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)) AND status = 'accepted' LIMIT 1");
        $stmt->execute([$a, $b, $b, $a]);
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    }
}
