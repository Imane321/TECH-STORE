<?php
// ============================================================
//  models/Cart.php
//  Panier géré en SESSION PHP (cours Ch.7 — sessions)
// ============================================================

class Cart {

    /**
     * Récupérer le panier depuis la session
     */
    public static function get(): array {
        return $_SESSION['cart'] ?? [];
    }

    /**
     * Ajouter ou incrémenter un produit dans le panier
     */
    public static function addItem(array $product, int $qty = 1): void {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        $id = $product['id'];

        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]['quantite'] += $qty;
        } else {
            $_SESSION['cart'][$id] = [
                'id'          => $id,
                'nom'         => $product['nom'],
                'prix'        => $product['prix'],
                'emoji'       => $product['emoji'] ?? '📦',
                'marque'      => $product['marque'] ?? '',
                'quantite'    => $qty,
            ];
        }
    }

    /**
     * Modifier la quantité d'un article
     */
    public static function updateItem(int $productId, int $qty): void {
        if ($qty <= 0) {
            self::removeItem($productId);
            return;
        }
        if (isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId]['quantite'] = $qty;
        }
    }

    /**
     * Supprimer un article du panier
     */
    public static function removeItem(int $productId): void {
        unset($_SESSION['cart'][$productId]);
    }

    /**
     * Vider le panier
     */
    public static function clear(): void {
        $_SESSION['cart'] = [];
    }

    /**
     * Calculer le total du panier
     */
    public static function getTotal(): float {
        $total = 0.0;
        foreach (self::get() as $item) {
            $total += $item['prix'] * $item['quantite'];
        }
        return $total;
    }

    /**
     * Nombre d'articles dans le panier
     */
    public static function getCount(): int {
        $count = 0;
        foreach (self::get() as $item) {
            $count += $item['quantite'];
        }
        return $count;
    }
}