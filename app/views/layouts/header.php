<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'NetChat' ?></title>
    
    <?php
    // Le $basePath est défini dans BaseController->view()
    // Si ce n'est pas le cas, utiliser la valeur par défaut
    if (!isset($basePath)) {
        $basePath = '/netchat/public';
    }
    ?>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $basePath ?>/assets/icon.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= $basePath ?>/assets/icon.png">
    <link rel="apple-touch-icon" href="<?= $basePath ?>/assets/icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="<?= $basePath ?>/style.css" rel="stylesheet">
    <?php if (isset($extra_css)): ?>
        <?php foreach ($extra_css as $css): ?>
            <link href="<?= $basePath ?>/<?= $css ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
