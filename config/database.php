<?php
/**
 * Configuration de la base de données
 */
class Database {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        try {
            // Connection to Aiven MySQL service
            $dsn = 'mysql:host=netchat-netchat.i.aivencloud.com;port=13911;dbname=netchat;charset=utf8mb4;ssl_mode=REQUIRED';
            $user = 'avnadmin';
            $pass = 'AVNS_-mk_dITiGa0x6UxHo_G';

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];

            // If PDO MySQL supports MYSQL_ATTR_SSL_MODE, request REQUIRED explicitly
            if (defined('PDO::MYSQL_ATTR_SSL_MODE')) {
                $options[constant('PDO::MYSQL_ATTR_SSL_MODE')] = 'REQUIRED';
            }

            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch(PDOException $e) {
            die("Erreur DB: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->pdo;
    }
}
