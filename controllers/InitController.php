<?php
namespace App\Controllers;
use PDO;
use PDOException;
use App\includes\Database;
use Exception;

class InitController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function InitDB()
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                email TEXT UNIQUE NOT NULL,
                password_hash TEXT NOT NULL,
                role TEXT DEFAULT 'user',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");

        // ตรวจสอบว่ามี admin อยู่แล้วหรือยัง
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
        $stmt->execute();
        if ($stmt->fetchColumn() == 0) {
            $passwordHash = password_hash('admin', PASSWORD_DEFAULT);
            $this->db->exec("INSERT INTO users (username, email, password_hash, role) VALUES ('admin','admin@admin.com','{$passwordHash}','admin');");
        }
    }
}
