<?php
// ============================================================
//  models/Category.php
//  Modèle Category — PDO (cours Ch.8)
// ============================================================

class Category {

    private int    $id;
    private string $nom;
    private string $slug;

    public function __construct(array $data = []) {
        $this->id   = $data['id']   ?? 0;
        $this->nom  = $data['nom']  ?? '';
        $this->slug = $data['slug'] ?? '';
    }

    public function getId():   int    { return $this->id; }
    public function getNom():  string { return $this->nom; }
    public function getSlug(): string { return $this->slug; }

    /**
     * Toutes les catégories
     */
    public static function getAll(PDO $pdo): array {
        try {
            $stmt = $pdo->query("SELECT * FROM categories ORDER BY nom");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
}