<?php
// Redirige vers la route MVC /logout qui est gérée par LogoutController.
// Cela évite la duplication de la logique (mise à jour SQL + destruction de session)
header('Location: /netchat/public/logout');
exit();
?>
