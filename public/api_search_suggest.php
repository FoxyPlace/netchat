<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

function nc_extract_hashtags(string $text): array {
    $out = [];
    if (preg_match_all('/#([A-Za-z0-9_]{1,50})\b/u', $text, $m)) {
        foreach ($m[1] as $t) {
            $out[] = $t;
        }
    }
    return $out;
}

try {
    $db = Database::getInstance()->getConnection();
    $currentUserId = (int)$_SESSION['user_id'];

    $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
    if (mb_strlen($q) < 1) {
        echo json_encode(['accounts' => [], 'hashtags' => []]);
        exit;
    }

    $needle = $q;
    $needleNoHash = ltrim($needle, '#');
    $likeUser = $needleNoHash . '%';

    // Comptes
    $accStmt = $db->prepare("
        SELECT id, username, profile_picture
        FROM users
        WHERE id != ? AND username LIKE ?
        ORDER BY username ASC
        LIMIT 8
    ");
    $accStmt->execute([$currentUserId, $likeUser]);
    $accounts = [];
    foreach ($accStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $accounts[] = [
            'type' => 'account',
            'label' => 'Compte',
            'id' => (int)$row['id'],
            'username' => $row['username'],
            'profile_picture' => $row['profile_picture'] ?: 'assets/user_icon.png',
        ];
    }

    // Hashtags : analyser quelques posts candidats
    $postStmt = $db->prepare("
        SELECT content FROM posts
        WHERE content LIKE ?
        ORDER BY created_at DESC
        LIMIT 120
    ");
    $postStmt->execute(['%' . $needleNoHash . '%']);
    $tagMap = [];
    foreach ($postStmt->fetchAll(PDO::FETCH_COLUMN) as $content) {
        foreach (nc_extract_hashtags((string)$content) as $t) {
            if ($t === '') {
                continue;
            }
            if (mb_stripos($t, $needleNoHash) !== false) {
                $key = mb_strtolower($t);
                if (!isset($tagMap[$key])) {
                    $tagMap[$key] = $t;
                }
            }
        }
    }
    ksort($tagMap);
    $hashtags = [];
    $i = 0;
    foreach ($tagMap as $canonical) {
        $hashtags[] = [
            'type' => 'hashtag',
            'label' => 'Hashtag',
            'tag' => $canonical,
            'display' => '#' . $canonical,
        ];
        if (++$i >= 6) {
            break;
        }
    }

    echo json_encode([
        'query' => $q,
        'accounts' => $accounts,
        'hashtags' => $hashtags,
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}
