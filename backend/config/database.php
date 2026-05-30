<?php
// ============================================================
//  config/database.php
//  Connexion à la BD avec PDO (méthode du cours Ch.8)
// ============================================================

define('DB_HOST',    'localhost');
define('DB_NAME',    'aykon_store');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

function connectPDO(): PDO {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
            DB_USER,
            DB_PASS
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES,   false); // Vraies requêtes préparées
        return $pdo;
    } catch (PDOException $e) {
        // Log l'erreur réelle côté serveur, jamais exposée au client
        error_log('[DB ERROR] ' . $e->getMessage());
        http_response_code(500);
        die(json_encode([
            'success' => false,
            'error'   => 'Erreur de connexion à la base de données.'
        ]));
    }
}