<?php
session_start();

// Autoloader simple
spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/../app/models/' . $class . '.php',
        __DIR__ . '/../app/controllers/' . $class . '.php',
        __DIR__ . '/../core/' . $class . '.php',
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Charger le routeur
require_once __DIR__ . '/../core/Router.php';

// Créer le routeur et définir les routes
$router = new Router();

// Routes d'authentification
$router->get('/login', 'AuthController', 'login');
$router->post('/login', 'AuthController', 'login');
$router->get('/register', 'AuthController', 'register');
$router->post('/register', 'AuthController', 'register');
$router->get('/logout', 'LogoutController', 'logout');

// Routes principales
$router->get('/', 'DashboardController', 'index');
$router->get('/dashboard', 'DashboardController', 'index');

// Notifications + Chat
$router->get('/notifications', 'NotificationsController', 'index');
$router->get('/chat', 'ChatController', 'index');
$router->get('/search', 'SearchController', 'index');

// Routes profil
$router->get('/profile', 'ProfileController', 'show');
$router->get('/profil', 'ProfileController', 'show');

// Routes paramètres
$router->get('/settings', 'SettingsController', 'index');
$router->post('/settings', 'SettingsController', 'index');

// Comments (MVC)
$router->post('/comment/create', 'CommentController', 'create');
$router->post('/comment/delete', 'CommentController', 'delete');

// Routes mot de passe
$router->get('/password/edit', 'PasswordController', 'edit');
$router->post('/password/edit', 'PasswordController', 'edit');

// Dispatch
$router->dispatch();
