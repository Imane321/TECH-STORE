<?php
// api/products.php
require_once __DIR__ . '/../config/config.php';
require_once BASE_PATH . '/controllers/ProductController.php';

$pdo    = connectPDO();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id     = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

match (true) {
    $method === 'GET'    && $action === 'categories' => ProductController::categories($pdo),
    $method === 'GET'    && $id === 0                => ProductController::index($pdo),
    $method === 'GET'    && $id > 0                  => ProductController::show($pdo, $id),
    $method === 'POST'   && $id === 0                => ProductController::store($pdo),
    $method === 'PUT'    && $id > 0                  => ProductController::update($pdo, $id),
    $method === 'DELETE' && $id > 0                  => ProductController::destroy($pdo, $id),
    default => (function() {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Route products introuvable']);
    })()
};