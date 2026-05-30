<?php
// ============================================================
//  controllers/ProductController.php
//  Logique métier — Produits (lecture publique + CRUD admin)
// ============================================================

require_once BASE_PATH . '/models/Product.php';
require_once BASE_PATH . '/models/Category.php';

class ProductController {

    /**
     * GET /api/products
     * Liste tous les produits (+ filtres : ?cat=pc&q=apple&sort=prix)
     */
    public static function index(PDO $pdo): void {
        $filters = [
            'categorie' => $_GET['cat']    ?? '',
            'q'         => $_GET['q']      ?? '',
            'marque'    => $_GET['marque'] ?? '',
            'sort'      => $_GET['sort']   ?? 'created_at',
        ];

        $products = Product::getAll($pdo, $filters);
        echo json_encode(['success' => true, 'data' => $products, 'total' => count($products)]);
    }

    /**
     * GET /api/products/{id}
     * Détail d'un produit
     */
    public static function show(PDO $pdo, int $id): void {
        $product = Product::findById($pdo, $id);
        if (!$product) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Produit introuvable']);
            return;
        }
        echo json_encode(['success' => true, 'data' => $product]);
    }

    /**
     * GET /api/products/categories
     * Liste des catégories
     */
    public static function categories(PDO $pdo): void {
        $cats = Category::getAll($pdo);
        echo json_encode(['success' => true, 'data' => $cats]);
    }

    /**
     * POST /api/products  (admin)
     * Créer un produit
     */
    public static function store(PDO $pdo): void {
        self::requireAdmin();
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['nom']) || empty($data['prix']) || empty($data['categorie_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Nom, prix et catégorie requis']);
            return;
        }

        $id = Product::create($pdo, $data);
        if (!$id) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Erreur création produit']);
            return;
        }

        http_response_code(201);
        echo json_encode(['success' => true, 'id' => $id]);
    }

    /**
     * PUT /api/products/{id}  (admin)
     * Modifier un produit
     */
    public static function update(PDO $pdo, int $id): void {
        self::requireAdmin();
        $data = json_decode(file_get_contents('php://input'), true);

        $ok = Product::update($pdo, $id, $data);
        echo json_encode(['success' => $ok]);
    }

    /**
     * DELETE /api/products/{id}  (admin)
     */
    public static function destroy(PDO $pdo, int $id): void {
        self::requireAdmin();
        $ok = Product::delete($pdo, $id);
        echo json_encode(['success' => $ok]);
    }

    // ── Middleware admin ───────────────────────────────────
    private static function requireAdmin(): void {
        if (($_SESSION['user_role'] ?? '') !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Accès refusé']);
            exit();
        }
    }
}