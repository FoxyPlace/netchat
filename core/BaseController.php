<?php
/**
 * Contrôleur de base
 */
class BaseController {
    protected $db;
    
    public function __construct() {
        require_once __DIR__ . '/../config/database.php';
        $this->db = Database::getInstance()->getConnection();
    }
    
    protected function view($viewName, $data = []) {
        require_once __DIR__ . '/helpers.php';
        // Définir le chemin de base pour les assets
        $data['basePath'] = '/netchat/public';
        extract($data);
        $viewPath = __DIR__ . '/../app/views/' . $viewName . '.php';
        if (!file_exists($viewPath)) {
            die("Vue non trouvée: " . htmlspecialchars($viewPath));
        }
        require $viewPath;
    }
    
    protected function redirect($url) {
        // If absolute URL, use it as-is
        if (preg_match('#^https?://#i', $url)) {
            header('Location: ' . $url);
            exit;
        }

        // If URL already targets the public folder, leave it
        if (strpos($url, '/netchat/public') === 0) {
            header('Location: ' . $url);
            exit;
        }

        // For routes starting with '/', prefix with /netchat/public so they resolve to the public router
        if (strpos($url, '/') === 0) {
            $url = '/netchat/public' . $url;
        }

        header('Location: ' . $url);
        exit;
    }
    
    protected function requireAuth() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }
    }
}
