<?php
// api/orders.php
require_once __DIR__ . '/../config/config.php';
require_once BASE_PATH . '/controllers/OrderController.php';

$pdo     = connectPDO();
$method  = $_SERVER['REQUEST_METHOD'];
$orderId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

match (true) {
    $method === 'GET'  && $orderId === 0 => OrderController::index($pdo),
    $method === 'GET'  && $orderId > 0   => OrderController::show($pdo, $orderId),
    $method === 'POST'                   => OrderController::store($pdo),
    default => (function() {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Route orders introuvable']);
    })()
};