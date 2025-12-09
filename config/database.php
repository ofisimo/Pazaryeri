<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'poysi_ofsm';
    private $username = 'poysi_ofsm';
    private $password = '5JARRL8bCwHBLYsu856u';
    private $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            die("Bağlantı hatası: " . $e->getMessage());
        }
        
        return $this->conn;
    }
}

// Global bağlantı
$database = new Database();
$db = $database->getConnection();