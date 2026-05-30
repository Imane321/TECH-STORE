<?php
// api/cart.php
require_once __DIR__ . '/../config/config.php';
require_once BASE_PATH . '/controllers/CartController.php';

$pdo       = connectPDO();
$method    = $_SERVER['REQUEST_METHOD'];
$productId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

match (true) {
    $method === 'GET'    && $productId === 0 => CartController::index(),
    $method === 'POST'   && $productId === 0 => CartController::add($pdo),
    $method === 'PUT'    && $productId > 0   => CartController::update($productId),
    $method === 'DELETE' && $productId > 0   => CartController::remove($productId),
    $method === 'DELETE' && $productId === 0 => CartController::clear(),
    default => (function() {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Route cart introuvable']);
    })()
};
?>