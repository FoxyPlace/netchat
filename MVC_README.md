# Structure MVC - NetChat

## Organisation des dossiers

```
netchat/
├── app/
│   ├── controllers/     # Contrôleurs (logique métier)
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── ProfileController.php
│   │   ├── SettingsController.php
│   │   └── PasswordController.php
│   ├── models/         # Modèles (accès BDD)
│   │   ├── User.php
│   │   └── Post.php
│   └── views/          # Vues (affichage HTML)
│       ├── layouts/
│       │   ├── header.php
│       │   └── footer.php
│       ├── auth/
│       │   ├── login.php
│       │   └── register.php
│       ├── dashboard/
│       ├── profile/
│       ├── settings/
│       └── password/
├── config/             # Configuration
│   └── database.php
├── core/               # Classes de base
│   ├── BaseController.php
│   └── Router.php
└── public/             # Point d'entrée et assets
    ├── index.php       # Point d'entrée unique
    ├── assets/         # Images, CSS, JS
    ├── style.css
    ├── dashboard.css
    ├── settingsprofile.css
    └── script.js
```

## Architecture MVC

### Models (app/models/)
- **User.php** : Gestion des utilisateurs (CRUD, authentification)
- **Post.php** : Gestion des posts (création, récupération)

### Views (app/views/)
- **layouts/** : Templates communs (header, footer)
- **auth/** : Pages de connexion/inscription
- **dashboard/** : Page d'accueil
- **profile/** : Pages de profil
- **settings/** : Pages de paramètres
- **password/** : Changement de mot de passe

### Controllers (app/controllers/)
- **AuthController** : Login, Register, Logout
- **DashboardController** : Page d'accueil
- **ProfileController** : Affichage profil
- **SettingsController** : Paramètres utilisateur
- **PasswordController** : Changement mot de passe

## Point d'entrée

Le fichier `public/index.php` est le point d'entrée unique de l'application. Il :
1. Initialise la session
2. Charge les classes (autoloader)
3. Configure le routeur
4. Dispatch les requêtes vers les bons contrôleurs

## Routes

Les routes sont définies dans `public/index.php` :
- `/login` → AuthController::login()
- `/register` → AuthController::register()
- `/dashboard` → DashboardController::index()
- `/profile` → ProfileController::show()
- `/settings` → SettingsController::index()
- `/password/edit` → PasswordController::edit()

## Configuration

Pour utiliser cette structure, il faut :
1. Configurer le serveur web pour pointer vers `public/` comme document root
2. Ou utiliser un fichier `.htaccess` pour rediriger toutes les requêtes vers `public/index.php`

## Migration depuis l'ancienne structure

Les anciens fichiers PHP (login.php, register.php, etc.) peuvent être conservés pour référence mais ne sont plus utilisés. Toute la logique a été déplacée dans la structure MVC.
