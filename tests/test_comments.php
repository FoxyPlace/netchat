<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/models/Comment.php';

$db = Database::getInstance()->getConnection();
$comment = new Comment($db);

// create comment as user 1 on post 1
$res = $comment->create(1, 1, 'Test commentaire from CLI @Malenia');
var_export($res);

if ($res && isset($res['id'])) {
    $id = (int)$res['id'];
    echo "\nCreated comment id: $id\n";
    $del = $comment->deleteById($id, 1);
    var_export($del);
}

?>