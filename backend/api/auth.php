<?php
// api/auth.php — appelé directement par le frontend
require_once __DIR__ . '/../config/config.php';
require_once BASE_PATH . '/controllers/UserController.php';

$pdo    = connectPDO();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

match (true) {
    $method === 'POST' && $action === 'login'    => UserController::login($pdo),
    $method === 'POST' && $action === 'register' => UserController::register($pdo),
    $method === 'POST' && $action === 'logout'   => UserController::logout(),
    $method === 'GET'  && $action === 'me'       => UserController::me($pdo),
    $method === 'PUT'  && $action === 'profile'  => UserController::updateProfile($pdo),
    default => (function() use ($action) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => "Action '$action' introuvable"]);
    })()
};
