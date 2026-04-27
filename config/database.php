<?php
/**
 * Konfigurasi Database MySQL
 * Menggunakan MySQLi untuk koneksi dan prepared statements
 */

class Database {
    private $host = 'localhost';
    private $username = 'root';
    private $password = '';
    private $database = 'swiftpedia_db';
    private $connection;

    /**
     * Membuka koneksi ke database
     * @return mysqli Object koneksi MySQLi
     */
    public function getConnection() {
        $this->connection = new mysqli($this->host, $this->username, $this->password, $this->database);
        
        if ($this->connection->connect_error) {
            die("Koneksi database gagal: " . $this->connection->connect_error);
        }
        
        $this->connection->set_charset("utf8");
        return $this->connection;
    }
}

// Inisialisasi koneksi global
$database = new Database();
$conn = $database->getConnection();
?>