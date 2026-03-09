<?php
/**
 * Routeur simple
 */
class Router {
    private $routes = [];
    
    public function get($path, $controller, $method) {
        $this->routes['GET'][$path] = ['controller' => $controller, 'method' => $method];
    }
    
    public function post($path, $controller, $method) {
        $this->routes['POST'][$path] = ['controller' => $controller, 'method' => $method];
    }
    
    public function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Nettoyer le chemin (enlever /netchat/public si présent)
        $path = str_replace('/netchat/public', '', $path);
        $path = str_replace('/netchat', '', $path);
        
        // Normaliser le chemin : enlever le slash final sauf pour la racine
        $path = rtrim($path, '/');
        $path = $path ?: '/';
        
        // Si c'est un fichier PHP direct dans public/ (comme api_posts.php, post.php), laisser passer
        if (preg_match('/\.php$/', $path) && $path !== '/index.php') {
            // Vérifier si le fichier existe dans public/
            $filePath = __DIR__ . '/../public' . $path;
            if (file_exists($filePath)) {
                require $filePath;
                return;
            }
        }
        
        if (isset($this->routes[$method][$path])) {
            $route = $this->routes[$method][$path];
            $controller = new $route['controller']();
            $method = $route['method'];
            $controller->$method();
        } else {
            // Route par défaut ou 404
            http_response_code(404);
            echo "Page non trouvée: " . htmlspecialchars($path);
        }
    }
}
