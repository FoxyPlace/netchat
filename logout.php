<?php
session_start();

if (isset($_SESSION['user_id'])) {
    try {
        $pdo = new PDO("mysql:host=127.0.0.1;dbname=netchat;charset=utf8mb4", 'root', '');
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    } catch(PDOException $e) {
        // Ignore erreur DB
    }
}

session_destroy();
header("Location: login.php");
exit();
?>
