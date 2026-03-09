<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    $postId = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
    $userId = (int)$_SESSION['user_id'];

    if ($postId <= 0) {
        echo json_encode(['error' => 'Post invalide']);
        exit;
    }

    // Vérifier que le post existe et que l'utilisateur est l'auteur
    $stmt = $db->prepare("SELECT * FROM posts WHERE id = ?");
    $stmt->execute([$postId]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$post) {
        echo json_encode(['error' => 'Post introuvable']);
        exit;
    }
    if ((int)$post['user_id'] !== $userId) {
        echo json_encode(['error' => 'Vous n\'êtes pas l\'auteur de ce post']);
        exit;
    }

    // Supprimer les commentaires associés (et leurs mentions)
    $cstmt = $db->prepare("SELECT id FROM comments WHERE post_id = ?");
    $cstmt->execute([$postId]);
    $commentIds = $cstmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($commentIds)) {
        // Supprimer mentions de commentaires
        $in = str_repeat('?,', count($commentIds) - 1) . '?';
        $delCommentMentions = $db->prepare("DELETE FROM comment_mentions WHERE comment_id IN ($in)");
        $delCommentMentions->execute($commentIds);

        // Supprimer les commentaires
        $delComments = $db->prepare("DELETE FROM comments WHERE id IN ($in)");
        $delComments->execute($commentIds);
    }

    // Supprimer mentions du post
    $delPostMentions = $db->prepare("DELETE FROM post_mentions WHERE post_id = ?");
    $delPostMentions->execute([$postId]);

    // Supprimer réactions
    $delReactions = $db->prepare("DELETE FROM reactions WHERE post_id = ?");
    $delReactions->execute([$postId]);

    // Si image présente, supprimer le fichier (sécurisé)
    if (!empty($post['image_url'])) {
        $imageUrl = $post['image_url'];
        // Seuls les chemins relatifs commençant par assets/ sont autorisés
        if (preg_match('#^assets/#', $imageUrl)) {
            $publicDir = realpath(__DIR__ . '/.. /public');
            // Fallback if spacing issue: compute correctly
            $publicDir = realpath(__DIR__ . '/../public');
            $filePath = realpath($publicDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $imageUrl));
            if ($filePath && strpos($filePath, $publicDir) === 0 && is_file($filePath)) {
                @unlink($filePath);
            }
        }
    }

    // Supprimer le post
    $delPost = $db->prepare("DELETE FROM posts WHERE id = ?");
    $delPost->execute([$postId]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}

?>
