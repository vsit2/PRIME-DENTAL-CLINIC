<?php
/**
 * Prime Dental Clinic Management System
 * Database Connection (PDO) with Auto-Installer
 */

require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
                ];
                
                try {
                    self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
                } catch (PDOException $e) {
                    // Database might not exist yet - attempt to create it
                    if ($e->getCode() == 1049 || str_contains($e->getMessage(), 'Unknown database')) {
                        self::autoInstallDatabase();
                        self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
                    } else {
                        throw $e;
                    }
                }
            } catch (PDOException $e) {
                die("<div style='font-family:sans-serif;padding:30px;background:#fef2f2;border:1px solid #ef4444;color:#991b1b;border-radius:8px;max-width:700px;margin:50px auto;box-shadow:0 10px 25px rgba(0,0,0,0.05);'>" .
                    "<h2 style='margin-top:0;'>Prime Dental Clinic - Database Connection Error</h2>" .
                    "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>" .
                    "<p>Please ensure MySQL service is running in XAMPP on port " . DB_PORT . ".</p>" .
                    "<a href='database/installer.php' style='display:inline-block;background:#0d9488;color:#fff;padding:10px 18px;text-decoration:none;border-radius:6px;font-weight:600;'>Run Database Installer</a>" .
                    "</div>");
            }
        }
        return self::$instance;
    }

    public static function autoInstallDatabase(): bool {
        try {
            $rootDsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET;
            $pdo = new PDO($rootDsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            
            $schemaFile = BASE_DIR . '/database/schema.sql';
            $seedFile = BASE_DIR . '/database/seed.sql';
            
            if (file_exists($schemaFile)) {
                $sql = file_get_contents($schemaFile);
                $pdo->exec($sql);
            }
            
            if (file_exists($seedFile)) {
                $sql = file_get_contents($seedFile);
                $pdo->exec($sql);
            }
            return true;
        } catch (Exception $e) {
            error_log("Database AutoInstall Failed: " . $e->getMessage());
            return false;
        }
    }
}

// Function alias for quick access
function getDB(): PDO {
    return Database::getConnection();
}
