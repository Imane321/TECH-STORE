<?php
// ============================================================
//  controllers/CartController.php
//  Logique métier — Panier (session PHP — cours Ch.7)
// ============================================================

require_once BASE_PATH . '/models/Cart.php';
require_once BASE_PATH . '/models/Product.php';

class CartController {

    /**
     * GET /api/cart
     * Retourner le contenu du panier
     */
    public static function index(): void {
        echo json_encode([
            'success' => true,
            'items'   => array_values(Cart::get()),
            'total'   => Cart::getTotal(),
            'count'   => Cart::getCount(),
        ]);
    }

    /**
     * POST /api/cart
     * Ajouter un produit au panier
     * Body : { "product_id": 1, "quantite": 2 }
     */
    public static function add(PDO $pdo): void {
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['product_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'product_id requis']);
            return;
        }

        $product = Product::findById($pdo, (int)$data['product_id']);
        if (!$product) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Produit introuvable']);
            return;
        }

        $qty = (int)($data['quantite'] ?? 1);
        if ($qty < 1) $qty = 1;

        Cart::addItem($product, $qty);

        echo json_encode([
            'success' => true,
            'message' => 'Produit ajouté au panier',
            'count'   => Cart::getCount(),
        ]);
    }

    /**
     * PUT /api/cart/{id}
     * Modifier la quantité d'un article
     */
    public static function update(int $productId): void {
        $data = json_decode(file_get_contents('php://input'), true);
        $qty  = (int)($data['quantite'] ?? 1);
        Cart::updateItem($productId, $qty);
        echo json_encode(['success' => true, 'total' => Cart::getTotal(), 'count' => Cart::getCount()]);
    }

    /**
     * DELETE /api/cart/{id}
     * Supprimer un article du panier
     */
    public static function remove(int $productId): void {
        Cart::removeItem($productId);
        echo json_encode(['success' => true, 'total' => Cart::getTotal(), 'count' => Cart::getCount()]);
    }

    /**
     * DELETE /api/cart
     * Vider le panier
     */
    public static function clear(): void {
        Cart::clear();
        echo json_encode(['success' => true, 'message' => 'Panier vidé']);
    }
}