<?php
// ============================================================
//  controllers/AdminController.php
//  Logique métier — Dashboard admin
// ============================================================

require_once BASE_PATH . '/models/User.php';
require_once BASE_PATH . '/models/Order.php';
require_once BASE_PATH . '/models/Product.php';

class AdminController {

    /**
     * GET /api/admin/stats
     * Statistiques du dashboard
     */
    public static function stats(PDO $pdo): void {
        self::requireAdmin();
        try {
            $users    = $pdo->query("SELECT COUNT(*) FROM users WHERE role='client'")->fetchColumn();
            $orders   = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
            $products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
            $revenue  = $pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE statut != 'annulee'")->fetchColumn();

            echo json_encode([
                'success' => true,
                'data' => [
                    'clients'     => (int)$users,
                    'commandes'   => (int)$orders,
                    'produits'    => (int)$products,
                    'chiffre_affaires' => (float)$revenue,
                ]
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Erreur stats']);
        }
    }

    /**
     * GET /api/admin/users
     * Liste tous les utilisateurs
     */
    public static function users(PDO $pdo): void {
        self::requireAdmin();
        $users = User::getAll($pdo);
        echo json_encode(['success' => true, 'data' => $users]);
    }

    /**
     * DELETE /api/admin/users/{id}
     * Supprimer un utilisateur
     */
    public static function deleteUser(PDO $pdo, int $id): void {
        self::requireAdmin();
        // Ne pas supprimer son propre compte
        if ($id === $_SESSION['user_id']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Impossible de supprimer votre propre compte']);
            return;
        }
        $ok = User::delete($pdo, $id);
        echo json_encode(['success' => $ok]);
    }

    /**
     * GET /api/admin/orders
     * Toutes les commandes
     */
    public static function orders(PDO $pdo): void {
        self::requireAdmin();
        $orders = Order::getAll($pdo);
        echo json_encode(['success' => true, 'data' => $orders]);
    }

    /**
     * PUT /api/admin/orders/{id}/statut
     * Changer le statut d'une commande
     */
    public static function updateOrderStatut(PDO $pdo, int $id): void {
        self::requireAdmin();
        $data   = json_decode(file_get_contents('php://input'), true);
        $statut = $data['statut'] ?? '';
        $ok     = Order::updateStatut($pdo, $id, $statut);
        echo json_encode(['success' => $ok]);
    }

    // ── Middleware ─────────────────────────────────────────
    private static function requireAdmin(): void {
        if (($_SESSION['user_role'] ?? '') !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Accès réservé aux administrateurs']);
            exit();
        }
    }
}