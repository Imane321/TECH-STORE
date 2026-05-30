-- ============================================================
--  tech_store.sql
--  Base de données : Aykon Tech Store
-- ============================================================

CREATE DATABASE IF NOT EXISTS aykon_store CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE aykon_store;

-- ── TABLE : users ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nom         VARCHAR(100) NOT NULL,
    prenom      VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,          -- hash bcrypt
    telephone   VARCHAR(20)  DEFAULT NULL,
    adresse     VARCHAR(255) DEFAULT NULL,
    ville       VARCHAR(100) DEFAULT NULL,
    role        ENUM('client','admin') DEFAULT 'client',
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── TABLE : categories ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS categories (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    nom     VARCHAR(100) NOT NULL,
    slug    VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- ── TABLE : products ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS products (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nom             VARCHAR(200) NOT NULL,
    description     TEXT         DEFAULT NULL,
    prix            DECIMAL(10,2) NOT NULL,
    prix_ancien     DECIMAL(10,2) DEFAULT NULL,
    stock           INT           DEFAULT 0,
    categorie_id    INT           NOT NULL,
    marque          VARCHAR(100)  DEFAULT NULL,
    badge           ENUM('new','sale','hot') DEFAULT NULL,
    emoji           VARCHAR(10)   DEFAULT '📦',
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categorie_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── TABLE : orders ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS orders (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT           NOT NULL,
    statut          ENUM('en_attente','confirmee','expediee','livree','annulee') DEFAULT 'en_attente',
    total           DECIMAL(10,2) NOT NULL,
    adresse_livraison VARCHAR(255) DEFAULT NULL,
    ville_livraison VARCHAR(100)  DEFAULT NULL,
    telephone       VARCHAR(20)   DEFAULT NULL,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── TABLE : order_items ────────────────────────────────────
CREATE TABLE IF NOT EXISTS order_items (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    order_id    INT           NOT NULL,
    product_id  INT           NOT NULL,
    quantite    INT           NOT NULL DEFAULT 1,
    prix_unitaire DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id)  REFERENCES orders(id)  ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── DONNÉES DE BASE ────────────────────────────────────────
INSERT INTO categories (nom, slug) VALUES
    ('Ordinateurs PC', 'pc'),
    ('Téléphones',     'phones'),
    ('Accessoires',    'accessories');

-- Admin par défaut (mot de passe : Admin1234)
INSERT INTO users (nom, prenom, email, mot_de_passe, role) VALUES
    ('Admin', 'Aykon', 'admin@aykon.ma',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
     'admin');

-- Quelques produits de démo
INSERT INTO products (nom, prix, prix_ancien, stock, categorie_id, marque, badge, emoji) VALUES
    ('MacBook Pro 14" M3',        18900, 21000,  5, 1, 'Apple',    'hot',  '💻'),
    ('iPhone 15 Pro Max',         14500, NULL,   8, 2, 'Apple',    'new',  '📱'),
    ('Galaxy S24 Ultra',          12900, 14000, 12, 2, 'Samsung',  'sale', '📱'),
    ('Dell XPS 15 OLED',          16500, NULL,   3, 1, 'Dell',     NULL,   '💻'),
    ('Sony WH-1000XM5',            2800,  3200, 20, 3, 'Sony',     'sale', '🎧'),
    ('Logitech MX Master 3',        890,  NULL,  35, 3, 'Logitech', 'hot',  '🖱️'),
    ('Asus ROG Zephyrus G14',     14200, 15000,  6, 1, 'Asus',     'sale', '💻'),
    ('Samsung Galaxy Tab S9',      5400,  NULL,  14, 3, 'Samsung',  'new',  '📟'),
    ('Xiaomi 14 Ultra',            9800, 10500, 18, 2, 'Xiaomi',   'sale', '📱'),
    ('Apple AirPods Pro 2',        2400,  NULL,  22, 3, 'Apple',    'hot',  '🎵'),
    ('Clavier Mécanique Keychron', 640,   750,  50, 3, 'Keychron', 'hot',  '⌨️'),
    ('Dell Alienware m18 R2',     24500, 26000,  2, 1, 'Dell',     'sale', '💻');
