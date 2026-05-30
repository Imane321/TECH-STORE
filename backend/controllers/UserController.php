<?php
// ============================================================
//  controllers/UserController.php
//  Logique métier — Inscription, Connexion, Profil
//  Sessions PHP (cours Ch.7) + PDO (cours Ch.8)
// ============================================================

require_once BASE_PATH . '/models/User.php';

class UserController {

    /**
     * POST /api/auth/register
     * Inscription d'un nouvel utilisateur
     */
    public static function register(PDO $pdo): void {
        $data = json_decode(file_get_contents('php://input'), true);

        // Validation
        if (empty($data['nom']) || empty($data['prenom']) ||
            empty($data['email']) || empty($data['mot_de_passe'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Champs obligatoires manquants']);
            return;
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Email invalide']);
            return;
        }

        if (strlen($data['mot_de_passe']) < 6) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Mot de passe trop court (min. 6 caractères)']);
            return;
        }

        // Vérifier si l'email existe déjà
        if (User::findByEmail($pdo, $data['email'])) {
            http_response_code(409);
            echo json_encode(['success' => false, 'error' => 'Cet email est déjà utilisé']);
            return;
        }

        // Créer le user
        $id = User::create($pdo, $data);
        if (!$id) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Erreur lors de la création du compte']);
            return;
        }

        http_response_code(201);
        echo json_encode(['success' => true, 'message' => 'Compte créé avec succès', 'id' => $id]);
    }

    /**
     * POST /api/auth/login
     * Connexion — démarre la session PHP
     */
    public static function login(PDO $pdo): void {
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['email']) || empty($data['mot_de_passe'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Email et mot de passe requis']);
            return;
        }

        $user = User::findByEmail($pdo, $data['email']);

        if (!$user || !password_verify($data['mot_de_passe'], $user['mot_de_passe'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Email ou mot de passe incorrect']);
            return;
        }

        // Régénérer l'ID de session après login → protection session fixation
        session_regenerate_id(true);

        // Stocker dans la session (cours Ch.7)
        $_SESSION['user_id']       = $user['id'];
        $_SESSION['user_role']     = $user['role'];
        $_SESSION['user_nom']      = $user['nom'];
        $_SESSION['last_activity'] = time();

        // Ne pas renvoyer le mot de passe
        unset($user['mot_de_passe']);

        echo json_encode(['success' => true, 'user' => $user]);
    }

    /**
     * POST /api/auth/logout
     * Déconnexion — détruire la session proprement
     */
    public static function logout(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']
            );
        }
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Déconnecté avec succès']);
    }

    /**
     * GET /api/auth/me
     * Retourner l'utilisateur connecté
     */
    public static function me(PDO $pdo): void {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Non connecté']);
            return;
        }

        $user = User::findById($pdo, $_SESSION['user_id']);
        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Utilisateur introuvable']);
            return;
        }

        echo json_encode(['success' => true, 'user' => $user]);
    }

    /**
     * PUT /api/auth/profile
     * Mettre à jour le profil
     */
    public static function updateProfile(PDO $pdo): void {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Non connecté']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['nom']) || empty($data['prenom'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Nom et prénom requis']);
            return;
        }

        // Vérifier unicité email si modifié
        if (!empty($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Email invalide']);
                return;
            }
            $existing = User::findByEmail($pdo, $data['email']);
            if ($existing && (int)$existing['id'] !== (int)$_SESSION['user_id']) {
                http_response_code(409);
                echo json_encode(['success' => false, 'error' => 'Cet email est déjà utilisé']);
                return;
            }
        }

        $ok = User::update($pdo, $_SESSION['user_id'], $data);
        echo json_encode(['success' => $ok, 'message' => $ok ? 'Profil mis à jour' : 'Erreur mise à jour']);
    }
}