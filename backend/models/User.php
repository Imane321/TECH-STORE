<?php
// ============================================================
//  models/User.php
//  Modèle User — PDO + POO (cours Ch.7 & Ch.8)
// ============================================================

class User {

    private int    $id;
    private string $nom;
    private string $prenom;
    private string $email;
    private string $mot_de_passe;
    private string $telephone;
    private string $adresse;
    private string $ville;
    private string $role;

    // ── Constructeur ──────────────────────────────────────
    public function __construct(array $data = []) {
        $this->id           = $data['id']           ?? 0;
        $this->nom          = $data['nom']          ?? '';
        $this->prenom       = $data['prenom']       ?? '';
        $this->email        = $data['email']        ?? '';
        $this->mot_de_passe = $data['mot_de_passe'] ?? '';
        $this->telephone    = $data['telephone']    ?? '';
        $this->adresse      = $data['adresse']      ?? '';
        $this->ville        = $data['ville']        ?? '';
        $this->role         = $data['role']         ?? 'client';
    }

    // ── Getters ───────────────────────────────────────────
    public function getId():    int    { return $this->id; }
    public function getNom():   string { return $this->nom; }
    public function getPrenom():string { return $this->prenom; }
    public function getEmail(): string { return $this->email; }
    public function getRole():  string { return $this->role; }

    // ── Setters ───────────────────────────────────────────
    public function setNom(string $nom):     void { $this->nom = $nom; }
    public function setPrenom(string $p):    void { $this->prenom = $p; }
    public function setEmail(string $e):     void { $this->email = $e; }
    public function setTelephone(string $t): void { $this->telephone = $t; }
    public function setAdresse(string $a):   void { $this->adresse = $a; }
    public function setVille(string $v):     void { $this->ville = $v; }

    // ── CRUD statiques (accès via PDO — cours Ch.8) ───────

    /**
     * Trouver un user par son email
     */
    public static function findByEmail(PDO $pdo, string $email): array|false {
        try {
            $sql  = "SELECT * FROM users WHERE email = :email LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':email' => $email]);
            return $stmt->fetch();          // FETCH_ASSOC (défini dans database.php)
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Trouver un user par son id
     */
    public static function findById(PDO $pdo, int $id): array|false {
        try {
            $stmt = $pdo->prepare("SELECT id, nom, prenom, email, telephone, adresse, ville, role, created_at FROM users WHERE id = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Créer un nouveau user
     */
    public static function create(PDO $pdo, array $data): int|false {
        try {
            $sql = "INSERT INTO users (nom, prenom, email, mot_de_passe, telephone, adresse, ville)
                    VALUES (:nom, :prenom, :email, :mdp, :tel, :adresse, :ville)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nom'     => $data['nom'],
                ':prenom'  => $data['prenom'],
                ':email'   => $data['email'],
                ':mdp'     => password_hash($data['mot_de_passe'], PASSWORD_BCRYPT),
                ':tel'     => $data['telephone'] ?? '',
                ':adresse' => $data['adresse']   ?? '',
                ':ville'   => $data['ville']     ?? '',
            ]);
            return (int) $pdo->lastInsertId();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Mettre à jour le profil d'un user
     */
    public static function update(PDO $pdo, int $id, array $data): bool {
        try {
            // Email optionnel — mis à jour seulement s'il est fourni
            $emailClause = !empty($data['email']) ? ', email = :email' : '';
            $sql = "UPDATE users SET nom = :nom, prenom = :prenom,
                    telephone = :tel, adresse = :adresse, ville = :ville
                    {$emailClause}
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $params = [
                ':nom'     => $data['nom'],
                ':prenom'  => $data['prenom'],
                ':tel'     => $data['telephone'] ?? '',
                ':adresse' => $data['adresse']   ?? '',
                ':ville'   => $data['ville']     ?? '',
                ':id'      => $id,
            ];
            if (!empty($data['email'])) {
                $params[':email'] = $data['email'];
            }
            $stmt->execute($params);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log('[User::update] ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Tous les users (admin seulement)
     */
    public static function getAll(PDO $pdo): array {
        try {
            $stmt = $pdo->query("SELECT id, nom, prenom, email, telephone, ville, role, created_at FROM users ORDER BY created_at DESC");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Supprimer un user
     */
    public static function delete(PDO $pdo, int $id): bool {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
}