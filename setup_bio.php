<?php
// Script pour ajouter le champ bio à la table users
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=netchat;charset=utf8mb4", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Vérifier si la colonne existe déjà
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'bio'");
    if ($stmt->rowCount() == 0) {
        // Ajouter la colonne bio
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `bio` TEXT NULL DEFAULT NULL AFTER `profile_picture`");
        echo "✅ Champ 'bio' ajouté avec succès à la table users !\n";
    } else {
        echo "ℹ️ Le champ 'bio' existe déjà dans la table users.\n";
    }
} catch(PDOException $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
}
?>
