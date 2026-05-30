<?php
// ============================================================
//  controllers/OrderController.php
//  Logique métier — Commandes
// ============================================================

require_once BASE_PATH . '/models/Order.php';
require_once BASE_PATH . '/models/Cart.php';
require_once BASE_PATH . '/models/Product.php';

class OrderController {

    /**
     * GET /api/orders
     * Commandes de l'utilisateur connecté
     */
    public static function index(PDO $pdo): void {
        self::requireAuth();
        $orders = Order::getByUser($pdo, $_SESSION['user_id']);
        echo json_encode(['success' => true, 'data' => $orders]);
    }

    /**
     * GET /api/orders/{id}
     * Détail d'une commande
     */
    public static function show(PDO $pdo, int $id): void {
        self::requireAuth();
        $isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';
        $userId  = $isAdmin ? 0 : $_SESSION['user_id'];

        $order = Order::getDetail($pdo, $id, $userId);
        if (!$order) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Commande introuvable']);
            return;
        }
        echo json_encode(['success' => true, 'data' => $order]);
    }

    /**
     * POST /api/orders
     * Passer une commande à partir du panier session
     */
    public static function store(PDO $pdo): void {
        self::requireAuth();

        $data  = json_decode(file_get_contents('php://input'), true);
        $items = Cart::get();

        if (empty($items)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Panier vide']);
            return;
        }

        // Vérification stock + création commande dans une seule transaction
        // → évite la race condition (deux users commandant le même article en même temps)
        try {
            $pdo->beginTransaction();

            $orderItems = [];
            foreach ($items as $item) {
                // SELECT FOR UPDATE : verrouille la ligne jusqu'au commit
                $stmt = $pdo->prepare(
                    "SELECT id, nom, stock FROM products WHERE id = :id FOR UPDATE"
                );
                $stmt->execute([':id' => $item['id']]);
                $product = $stmt->fetch();

                if (!$product || $product['stock'] < $item['quantite']) {
                    $pdo->rollBack();
                    http_response_code(409);
                    echo json_encode([
                        'success' => false,
                        'error'   => 'Stock insuffisant pour : ' . ($product['nom'] ?? $item['nom'])
                    ]);
                    return;
                }

                $orderItems[] = [
                    'product_id'    => $item['id'],
                    'quantite'      => $item['quantite'],
                    'prix_unitaire' => $item['prix'],
                ];
            }

            // Insérer la commande
            $sqlOrder = "INSERT INTO orders (user_id, total, adresse_livraison, ville_livraison, telephone)
                         VALUES (:user_id, :total, :adresse, :ville, :tel)";
            $stmt = $pdo->prepare($sqlOrder);
            $stmt->execute([
                ':user_id' => $_SESSION['user_id'],
                ':total'   => Cart::getTotal(),
                ':adresse' => $data['adresse']   ?? '',
                ':ville'   => $data['ville']     ?? '',
                ':tel'     => $data['telephone'] ?? '',
            ]);
            $orderId = (int) $pdo->lastInsertId();

            // Insérer les lignes + décrémenter le stock
            $sqlItem = "INSERT INTO order_items (order_id, product_id, quantite, prix_unitaire)
                        VALUES (:order_id, :product_id, :qty, :prix)";
            $stmtItem = $pdo->prepare($sqlItem);

            $sqlStock = "UPDATE products SET stock = stock - :qty WHERE id = :id AND stock >= :qty";
            $stmtStock = $pdo->prepare($sqlStock);

            foreach ($orderItems as $item) {
                $stmtItem->execute([
                    ':order_id'   => $orderId,
                    ':product_id' => $item['product_id'],
                    ':qty'        => $item['quantite'],
                    ':prix'       => $item['prix_unitaire'],
                ]);
                $stmtStock->execute([
                    ':qty' => $item['quantite'],
                    ':id'  => $item['product_id'],
                ]);
            }

            $pdo->commit();

        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log('[OrderController::store] ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Erreur lors de la création de la commande']);
            return;
        }

        // Vider le panier après succès
        Cart::clear();

        http_response_code(201);
        echo json_encode(['success' => true, 'order_id' => $orderId, 'message' => 'Commande passée avec succès']);
    }

    // ── Middlewares ────────────────────────────────────────
    private static function requireAuth(): void {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Connexion requise']);
            exit();
        }
    }
}