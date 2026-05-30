<?php
// ============================================================
//  backend/index.php
//  Router principal — aiguille les requêtes vers les bons fichiers API
// ============================================================

require_once __DIR__ . '/config/config.php';

// Récupérer le chemin de la requête
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri    = rtrim($uri, '/');
$method = $_SERVER['REQUEST_METHOD'];

// Table de routage
$routes = [
    '/api/auth'     => __DIR__ . '/api/auth.php',
    '/api/products' => __DIR__ . '/api/products.php',
    '/api/cart'     => __DIR__ . '/api/cart.php',
    '/api/orders'   => __DIR__ . '/api/orders.php',
    '/api/admin'    => __DIR__ . '/api/admin.php',
];

// Chercher la route correspondante
foreach ($routes as $prefix => $file) {
    if (str_starts_with($uri, $prefix)) {
        require_once $file;
        exit();
    }
}

// Route introuvable
http_response_code(404);
echo json_encode(['success' => false, 'error' => 'Route introuvable']);