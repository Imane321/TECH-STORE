<?php
// ============================================================
//  models/Order.php
//  Modèle Order — PDO + POO (cours Ch.7 & Ch.8)
// ============================================================

class Order {

    private int    $id;
    private int    $user_id;
    private string $statut;
    private float  $total;
    private string $adresse_livraison;
    private string $ville_livraison;
    private string $telephone;

    // ── Constructeur ──────────────────────────────────────
    public function __construct(array $data = []) {
        $this->id                = $data['id']                ?? 0;
        $this->user_id           = $data['user_id']           ?? 0;
        $this->statut            = $data['statut']            ?? 'en_attente';
        $this->total             = (float)($data['total']     ?? 0);
        $this->adresse_livraison = $data['adresse_livraison'] ?? '';
        $this->ville_livraison   = $data['ville_livraison']   ?? '';
        $this->telephone         = $data['telephone']         ?? '';
    }

    // ── Getters ───────────────────────────────────────────
    public function getId():     int    { return $this->id; }
    public function getUserId(): int    { return $this->user_id; }
    public function getStatut(): string { return $this->statut; }
    public function getTotal():  float  { return $this->total; }

    // ── CRUD statiques ────────────────────────────────────

    /**
     * Créer une commande + ses lignes (transaction PDO)
     * $items = [['product_id'=>1,'quantite'=>2,'prix_unitaire'=>1490.00], ...]
     */
    public static function create(PDO $pdo, array $orderData, array $items): int|false {
        try {
            $pdo->beginTransaction();

            // 1. Insérer la commande
            $sql = "INSERT INTO orders (user_id, total, adresse_livraison, ville_livraison, telephone)
                    VALUES (:user_id, :total, :adresse, :ville, :tel)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':user_id' => $orderData['user_id'],
                ':total'   => $orderData['total'],
                ':adresse' => $orderData['adresse_livraison'] ?? '',
                ':ville'   => $orderData['ville_livraison']   ?? '',
                ':tel'     => $orderData['telephone']         ?? '',
            ]);
            $orderId = (int) $pdo->lastInsertId();

            // 2. Insérer les lignes de commande
            $sqlItem = "INSERT INTO order_items (order_id, product_id, quantite, prix_unitaire)
                        VALUES (:order_id, :product_id, :qty, :prix)";
            $stmtItem = $pdo->prepare($sqlItem);

            foreach ($items as $item) {
                $stmtItem->execute([
                    ':order_id'   => $orderId,
                    ':product_id' => $item['product_id'],
                    ':qty'        => $item['quantite'],
                    ':prix'       => $item['prix_unitaire'],
                ]);
            }

            $pdo->commit();
            return $orderId;

        } catch (PDOException $e) {
            $pdo->rollBack();
            return false;
        }
    }

    /**
     * Commandes d'un user
     */
    public static function getByUser(PDO $pdo, int $userId): array {
        try {
            $sql  = "SELECT * FROM orders WHERE user_id = :uid ORDER BY created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':uid' => $userId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Détail d'une commande (lignes + produits)
     */
    public static function getDetail(PDO $pdo, int $orderId, int $userId = 0): array|false {
        try {
            // Vérifier l'accès
            $sql = "SELECT * FROM orders WHERE id = :id";
            if ($userId > 0) $sql .= " AND user_id = :uid";
            $stmt = $pdo->prepare($sql);
            $params = [':id' => $orderId];
            if ($userId > 0) $params[':uid'] = $userId;
            $stmt->execute($params);
            $order = $stmt->fetch();
            if (!$order) return false;

            // Lignes de commande
            $sqlItems = "SELECT oi.*, p.nom, p.emoji, p.marque
                         FROM order_items oi
                         JOIN products p ON oi.product_id = p.id
                         WHERE oi.order_id = :oid";
            $stmtI = $pdo->prepare($sqlItems);
            $stmtI->execute([':oid' => $orderId]);
            $order['items'] = $stmtI->fetchAll();

            return $order;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Toutes les commandes (admin)
     */
    public static function getAll(PDO $pdo): array {
        try {
            $sql  = "SELECT o.*, u.nom AS user_nom, u.prenom AS user_prenom, u.email AS user_email
                     FROM orders o
                     JOIN users u ON o.user_id = u.id
                     ORDER BY o.created_at DESC";
            $stmt = $pdo->query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Changer le statut d'une commande (admin)
     */
    public static function updateStatut(PDO $pdo, int $id, string $statut): bool {
        $allowed = ['en_attente','confirmee','expediee','livree','annulee'];
        if (!in_array($statut, $allowed)) return false;
        try {
            $stmt = $pdo->prepare("UPDATE orders SET statut = :statut WHERE id = :id");
            $stmt->execute([':statut' => $statut, ':id' => $id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
}