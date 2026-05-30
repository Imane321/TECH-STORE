<?php
// ============================================================
//  config/config.php
//  Configuration générale de l'application
// ============================================================

// ── Origines autorisées (CORS) ────────────────────────────
// Remplace '*' par les domaines réels en production
$allowedOrigins = [
    'http://localhost',
    'http://localhost:3000',
    'http://127.0.0.1',
    'http://127.0.0.1:5500',   // Live Server VS Code
    'http://localhost:5500',
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
} else {
    // En développement local sans Origin header (ex: Postman, curl)
    if (empty($origin)) {
        header('Access-Control-Allow-Origin: *');
    }
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// Répondre aux requêtes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ── Session sécurisée ─────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,           // Session cookie (expire à la fermeture du nav)
        'path'     => '/',
        'secure'   => false,       // Mettre true en HTTPS / production
        'httponly' => true,        // Inaccessible via JS → protection XSS
        'samesite' => 'Lax',       // Protection CSRF de base
    ]);
    session_start();
}

// Timeout de session : 2 heures d'inactivité
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 7200) {
    session_unset();
    session_destroy();
    session_start();
}
$_SESSION['last_activity'] = time();

// Chemin racine du backend
define('BASE_PATH', dirname(__DIR__));

// Inclure la connexion BD
require_once BASE_PATH . '/config/database.php';