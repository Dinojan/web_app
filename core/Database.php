<?php
// core/Database.php - Simple PDO wrapper
class Database {
    private $pdo;
    public function __construct($config) {
        $dsn = $config['driver'] . ':host=' . $config['host'] . ';port=' . $config['port'] . ';dbname=' . $config['database'] . ';charset=utf8mb4';
        $this->pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    public function get($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }
    public function first($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }
}
// Global DB instance
function db() {
    static $instance;
    if (!$instance) {
        $config = require 'config/database.php';
        $conn = $config['connections'][$config['default']] ?? [];
        $instance = new Database($conn);
    }
    return $instance;
}