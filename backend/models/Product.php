<?php
// ============================================================
//  models/Product.php
//  Modèle Product — PDO + POO (cours Ch.7 & Ch.8)
// ============================================================

class Product {

    private int    $id;
    private string $nom;
    private string $description;
    private float  $prix;
    private ?float $prix_ancien;
    private int    $stock;
    private int    $categorie_id;
    private string $marque;
    private ?string $badge;
    private string $emoji;

    // ── Constructeur ──────────────────────────────────────
    public function __construct(array $data = []) {
        $this->id           = $data['id']           ?? 0;
        $this->nom          = $data['nom']          ?? '';
        $this->description  = $data['description']  ?? '';
        $this->prix         = (float)($data['prix'] ?? 0);
        $this->prix_ancien  = isset($data['prix_ancien']) ? (float)$data['prix_ancien'] : null;
        $this->stock        = (int)($data['stock']  ?? 0);
        $this->categorie_id = (int)($data['categorie_id'] ?? 0);
        $this->marque       = $data['marque']       ?? '';
        $this->badge        = $data['badge']        ?? null;
        $this->emoji        = $data['emoji']        ?? '📦';
    }

    // ── Getters ───────────────────────────────────────────
    public function getId():         int     { return $this->id; }
    public function getNom():        string  { return $this->nom; }
    public function getPrix():       float   { return $this->prix; }
    public function getStock():      int     { return $this->stock; }
    public function getCategorieId():int     { return $this->categorie_id; }

    // ── CRUD statiques ────────────────────────────────────

    /**
     * Tous les produits (avec filtre optionnel par catégorie / recherche)
     */
    public static function getAll(PDO $pdo, array $filters = []): array {
        try {
            $sql    = "SELECT p.*, c.slug AS categorie_slug, c.nom AS categorie_nom
                       FROM products p
                       JOIN categories c ON p.categorie_id = c.id
                       WHERE 1=1";
            $params = [];

            if (!empty($filters['categorie'])) {
                $sql .= " AND c.slug = :cat";
                $params[':cat'] = $filters['categorie'];
            }
            if (!empty($filters['q'])) {
                $sql .= " AND (p.nom LIKE :q OR p.marque LIKE :q)";
                $params[':q'] = '%' . $filters['q'] . '%';
            }
            if (!empty($filters['marque'])) {
                $sql .= " AND p.marque = :marque";
                $params[':marque'] = $filters['marque'];
            }

            // Tri
            $sort = $filters['sort'] ?? 'created_at';
            $allowedSorts = ['prix', 'prix_asc', 'nom', 'created_at'];
            if ($sort === 'prix_asc') {
                $sql .= " ORDER BY p.prix ASC";
            } elseif (in_array($sort, $allowedSorts)) {
                $sql .= " ORDER BY p." . $sort . " DESC";
            } else {
                $sql .= " ORDER BY p.created_at DESC";
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Un seul produit par id
     */
    public static function findById(PDO $pdo, int $id): array|false {
        try {
            $sql  = "SELECT p.*, c.slug AS categorie_slug, c.nom AS categorie_nom
                     FROM products p
                     JOIN categories c ON p.categorie_id = c.id
                     WHERE p.id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Créer un produit (admin)
     */
    public static function create(PDO $pdo, array $data): int|false {
        try {
            $sql = "INSERT INTO products (nom, description, prix, prix_ancien, stock, categorie_id, marque, badge, emoji)
                    VALUES (:nom, :desc, :prix, :prix_anc, :stock, :cat, :marque, :badge, :emoji)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nom'      => $data['nom'],
                ':desc'     => $data['description']  ?? '',
                ':prix'     => $data['prix'],
                ':prix_anc' => $data['prix_ancien']  ?? null,
                ':stock'    => $data['stock']        ?? 0,
                ':cat'      => $data['categorie_id'],
                ':marque'   => $data['marque']       ?? '',
                ':badge'    => $data['badge']        ?? null,
                ':emoji'    => $data['emoji']        ?? '📦',
            ]);
            return (int) $pdo->lastInsertId();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Modifier un produit (admin)
     */
    public static function update(PDO $pdo, int $id, array $data): bool {
        try {
            $sql = "UPDATE products SET nom = :nom, description = :desc, prix = :prix,
                    prix_ancien = :prix_anc, stock = :stock, categorie_id = :cat,
                    marque = :marque, badge = :badge, emoji = :emoji
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nom'      => $data['nom'],
                ':desc'     => $data['description']  ?? '',
                ':prix'     => $data['prix'],
                ':prix_anc' => $data['prix_ancien']  ?? null,
                ':stock'    => $data['stock']        ?? 0,
                ':cat'      => $data['categorie_id'],
                ':marque'   => $data['marque']       ?? '',
                ':badge'    => $data['badge']        ?? null,
                ':emoji'    => $data['emoji']        ?? '📦',
                ':id'       => $id,
            ]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Supprimer un produit (admin)
     */
    public static function delete(PDO $pdo, int $id): bool {
        try {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Décrémenter le stock (après commande)
     */
    public static function decrementStock(PDO $pdo, int $id, int $qty): bool {
        try {
            $stmt = $pdo->prepare("UPDATE products SET stock = stock - :qty WHERE id = :id AND stock >= :qty");
            $stmt->execute([':qty' => $qty, ':id' => $id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
}