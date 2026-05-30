<?php
// api/admin.php
require_once __DIR__ . '/../config/config.php';
require_once BASE_PATH . '/controllers/AdminController.php';

$pdo    = connectPDO();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id     = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

match (true) {
    $method === 'GET'    && $action === 'stats'         => AdminController::stats($pdo),
    $method === 'GET'    && $action === 'users'         => AdminController::users($pdo),
    $method === 'DELETE' && $action === 'users' && $id > 0 => AdminController::deleteUser($pdo, $id),
    $method === 'GET'    && $action === 'orders'        => AdminController::orders($pdo),
    $method === 'PUT'    && $action === 'orders' && $id > 0 => AdminController::updateOrderStatut($pdo, $id),
    default => (function() use ($action) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => "Action admin '$action' introuvable"]);
    })()
};