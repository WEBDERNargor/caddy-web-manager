<?php
namespace App\includes;
use PDO;
use PDOException;

class Database
{
    private static $instance = null;
    private $pdo;
    private $config;

    private function __construct()
    {
        $this->config = require __DIR__ . '/../config.php';

        try {
            $driver = $this->config['db']['driver'] ?? 'mysql';
            if ($driver === 'none') {
                $this->pdo = null;
                return;
            }

            if ($driver === 'sqlite') {
                $path = $this->config['db']['sqlite_path'] ?? (__DIR__ . '/../storage/database.sqlite');
                $dir = dirname($path);
                if (!is_dir($dir)) {
                    @mkdir($dir, 0777, true);
                }
                $dsn = "sqlite:" . $path;
                $this->pdo = new PDO(
                    $dsn,
                    null,
                    null,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
                return;
            }

        
            $dbname = $this->config['db']['database'] ?? '';
            $dsn = sprintf(
                "mysql:host=%s;port=%s;dbname=%s;charset=%s",
                $this->config['db']['host'],
                $this->config['db']['port'],
                $dbname,
                $this->config['db']['charset']
            );
            $this->pdo = new PDO(
                $dsn,
                $this->config['db']['username'],
                $this->config['db']['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            error_log('Database connection error: ' . $e->getMessage());
            die("Connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance->pdo; 
    }
}
