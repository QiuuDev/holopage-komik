<?php
// config/database.php

class Database {
    private $host = 'localhost'; // Ganti jika hosting memberikan host berbeda
    private $db_name = "holopage_db";
    private $username = 'root'; // Sesuaikan dengan user database hosting
    private $password = '';     // Sesuaikan dengan password database hosting
    public $conn;

    public function getConnection() {
        $this->host = getenv("DB_HOST") ?: $this->host;
        $this->db_name = getenv("DB_NAME") ?: $this->db_name;
        $this->username = getenv("DB_USER") ?: $this->username;
        $this->password = getenv("DB_PASS") ?: $this->password;

        $this->conn = null;

        try {
            // Menggunakan PDO untuk keamanan maksimal
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            // Set error mode ke exception agar mudah debugging
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // Set default fetch mode ke Associative Array
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            // Support karakter utf8 (penting untuk komentar/emoji)
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
?>