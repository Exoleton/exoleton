CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('customer','admin') DEFAULT 'customer',
  reset_token VARCHAR(255) DEFAULT NULL,
  reset_expires DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO users (name, email, password_hash, role)
VALUES ('franck BODO', 'contact@exoleton.com', '$2y$12$45OnS4TSar6egTu3MLcuWO3md5dQIietEXGD6cIOKL1h2xs3hm.KW', 'admin')
ON DUPLICATE KEY UPDATE name=VALUES(name), role=VALUES(role);

CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(150) NOT NULL UNIQUE,
  default_label VARCHAR(190) NOT NULL,
  translations JSON DEFAULT NULL,
  scope ENUM('product','guide','all') NOT NULL DEFAULT 'product',
  sort_order INT DEFAULT 0,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO categories (slug, default_label, translations, scope, sort_order, is_active) VALUES
  ('industriel', 'Industriel', '{"fr":"Industriel","en":"Industrial","de":"Industriell","it":"Industriale","es":"Industrial","pt":"Industrial","nl":"Industrieel","pl":"Przemys\u0142owy","jp":"\u7523\u696d\u7528","zh":"\u5de5\u4e1a","kr":"\uc0b0\uc5c5\uc6a9","ru":"\u041f\u0440\u043e\u043c\u044b\u0448\u043b\u0435\u043d\u043d\u044b\u0439"}', 'product', 1, 1)
ON DUPLICATE KEY UPDATE default_label=VALUES(default_label), translations=VALUES(translations), scope=VALUES(scope), sort_order=VALUES(sort_order), is_active=VALUES(is_active);

INSERT INTO categories (slug, default_label, translations, scope, sort_order, is_active) VALUES
  ('medical', 'Médical', '{"fr":"Médical","en":"Medical","de":"Medizinisch","it":"Medico","es":"Médico","pt":"Médico","nl":"Medisch","pl":"Medyczny","jp":"\u533b\u7642\u7528","zh":"\u533b\u7597","kr":"\uc758\ub8cc\uc6a9","ru":"\u041c\u0435\u0434\u0438\u0446\u0438\u043d\u0441\u043a\u0438\u0439"}', 'product', 2, 1)
ON DUPLICATE KEY UPDATE default_label=VALUES(default_label), translations=VALUES(translations), scope=VALUES(scope), sort_order=VALUES(sort_order), is_active=VALUES(is_active);

INSERT INTO categories (slug, default_label, translations, scope, sort_order, is_active) VALUES
  ('particulier', 'Particulier / Quotidien', '{"fr":"Particulier / Quotidien","en":"Personal / Daily","de":"Privat / Alltag","it":"Personale / Quotidiano","es":"Particular / Diario","pt":"Particular / Diário","nl":"Particulier / Dagelijks","pl":"Osobisty / Codzienny","jp":"\u500b\u4eba/\u65e5\u5e38","zh":"\u4e2a\u4eba/\u65e5\u7528","kr":"\uac1c\uc778/\uc77c\uc0c1","ru":"\u041f\u0435\u0440\u0441\u043e\u043d\u0430\u043b\u044c\u043d\u044b\u0439 / \u041f\u043e\u0432\u0441\u0435\u0434\u043d\u0435\u0432\u043d\u044b\u0439"}', 'product', 3, 1)
ON DUPLICATE KEY UPDATE default_label=VALUES(default_label), translations=VALUES(translations), scope=VALUES(scope), sort_order=VALUES(sort_order), is_active=VALUES(is_active);

INSERT INTO categories (slug, default_label, translations, scope, sort_order, is_active) VALUES
  ('collectivites', 'Collectivités / Soins', '{"fr":"Collectivités / Soins","en":"Communities / Care","de":"Gemeinschaften / Pflege","it":"Collettività / Cura","es":"Colectividades / Cuidados","pt":"Coletividades / Cuidados","nl":"Gemeenschappen / Zorg","pl":"Społeczności / Opieka","jp":"\u56e3\u4f53/\u30b1\u30a2","zh":"\u96c6\u4f53/\u62a4\u7406","kr":"\ub2e8\uccb4/\ucf00\uc5b4","ru":"\u0421\u043e\u043e\u0431\u0449\u0435\u0441\u0442\u0432\u0430 / \u0417\u0430\u0431\u043e\u0442\u0430"}', 'product', 4, 1)
ON DUPLICATE KEY UPDATE default_label=VALUES(default_label), translations=VALUES(translations), scope=VALUES(scope), sort_order=VALUES(sort_order), is_active=VALUES(is_active);

INSERT INTO categories (slug, default_label, translations, scope, sort_order, is_active) VALUES
  ('guides', 'Guides & ressources', '{"fr":"Guides & ressources","en":"Guides & resources","de":"Leitfäden & Ressourcen","it":"Guide e risorse","es":"Guías y recursos","pt":"Guias e recursos","nl":"Gidsen en resources","pl":"Przewodniki i zasoby","jp":"\u30ac\u30a4\u30c9\uff06\u30ea\u30bd\u30fc\u30b9","zh":"\u6307\u5357\u4e0e\u8d44\u6e90","kr":"\uac00\uc774\ub4dc \ubc0f \ub9ac\uc18c\uc2a4","ru":"\u0413\u0430\u0439\u0434\u044b \u0438 \u0440\u0435\u0441\u0443\u0440\u0441\u044b"}', 'guide', 5, 1)
ON DUPLICATE KEY UPDATE default_label=VALUES(default_label), translations=VALUES(translations), scope=VALUES(scope), sort_order=VALUES(sort_order), is_active=VALUES(is_active);

CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(150) NOT NULL UNIQUE,
  name VARCHAR(150) NOT NULL,
  category VARCHAR(150) NOT NULL,
  category_slug VARCHAR(150) DEFAULT NULL,
  tag VARCHAR(100) NOT NULL,
  price INT NULL,
  currency VARCHAR(3) NOT NULL DEFAULT 'EUR',
  summary TEXT,
  baseline TEXT,
  brand VARCHAR(100) DEFAULT 'Exoleton',
  availability VARCHAR(255) DEFAULT 'https://schema.org/InStock',
  type VARCHAR(100),
  weight VARCHAR(50),
  autonomy VARCHAR(50),
  charge VARCHAR(50),
  main_image VARCHAR(255),
  hero_image VARCHAR(255),
  bullets TEXT,
  tags TEXT,
  featured_order INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS product_images (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  url VARCHAR(255) NOT NULL,
  sort_order INT DEFAULT 0,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS product_downloads (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  label VARCHAR(255) NOT NULL,
  href VARCHAR(255) NOT NULL,
  sort_order INT DEFAULT 0,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS product_use_cases (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  title VARCHAR(150) NOT NULL,
  kpi VARCHAR(50) NOT NULL,
  description TEXT,
  sort_order INT DEFAULT 0,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS product_specs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  label VARCHAR(150) NOT NULL,
  value VARCHAR(255) NOT NULL,
  sort_order INT DEFAULT 0,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS product_alternatives (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  alt_name VARCHAR(150) NOT NULL,
  alt_slug VARCHAR(150) DEFAULT NULL,
  tag VARCHAR(100) NOT NULL,
  summary VARCHAR(255) NOT NULL,
  price INT NULL,
  image VARCHAR(255) NOT NULL,
  sort_order INT DEFAULT 0,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS suppliers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  contact_name VARCHAR(150) DEFAULT NULL,
  email VARCHAR(190) DEFAULT NULL,
  phone VARCHAR(50) DEFAULT NULL,
  dropshipping_enabled TINYINT(1) DEFAULT 1,
  is_featured TINYINT(1) DEFAULT 0,
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sécurise la compatibilité avec les bases déjà existantes
ALTER TABLE suppliers ADD COLUMN IF NOT EXISTS is_featured TINYINT(1) DEFAULT 0;

INSERT INTO suppliers (name, contact_name, email, phone, dropshipping_enabled, is_featured, notes) VALUES
  ('HMT France (Human Mechanical Technologies)', NULL, 'contact@hmt-france.com', '+33 (0)5 62 37 74 75', 1, 1, 'Segment : Exos pro/TMS, industrie, logistique, BTP. Adresse : 9 rue de la Garounère, 65000 Tarbes. Site : https://hmt-france.com'),
  ('ErgoSanté / ShivaExo', NULL, 'contact@ergosante.fr', '+33 (0)4 66 24 54 56', 1, 1, 'Segment : Exos industriels (ShivaExo), ergonomie, TMS. Adresse : 28 ZA de Labahou, 30140 Anduze. Site : https://ergosante.fr'),
  ('RB3D', NULL, NULL, '+33 (0)3 86 46 92 58', 1, 0, 'Segment : Exos actifs et passifs (Exoback, Exoviti…), cobotique. Adresse : 41 avenue de Paris, 89470 Monéteau. Contact via https://www.rb3d.com/fr/contact/'),
  ('Exhauss', NULL, 'contact@exhauss.com', NULL, 1, 0, 'Segment : Exos passifs haute assistance (forestier, paysager, manutention). Adresse : 13 rue Daniel Fargeot, 69550 Amplepuis. Site : https://www.exhauss.com'),
  ('Japet Medical (Japet.W+)', NULL, 'contact@japet.eu', '+33 (0)3 74 09 57 52', 1, 0, 'Segment : Exo lombaire médical/tertiaire, prévention mal de dos. Adresse : 147 av Pierre Mauroy, 59120 Loos. Site : https://www.japet.eu'),
  ('Exoskelette.com (AWB GmbH)', NULL, 'office@awb.at', '+43 7245 20513-0', 1, 0, 'Segment : Distributeur multi-marques (Ottobock/Paexo, Auxivo, Noonee, Armon, Bioservo…). Adresse : Moritz-von-Schwind-Straße 10a, 4651 Stadl-Paura. Site : https://www.exoskelette.com'),
  ('German Bionic', NULL, 'sales@germanbionic.com', '+49 (0)821 209 871 63', 1, 1, 'Segment : Exos actifs AI (Cray X, Apogee) logistique/industrie. Contact France : contact-france@germanbionic.com / +33 (0)7 89 34 58 23. Site : https://www.germanbionic.com'),
  ('Auxivo', NULL, 'info@auxivo.com', '+41 77 250 35 31', 1, 0, 'Segment : Exos passifs industrie (LiftSuit, DeltaSuit, CarrySuit) + kits éducatifs. Adresse : Sonnenbergstrasse 74, 8603 Schwerzenbach. Site : https://www.auxivo.com'),
  ('Laevo B.V.', NULL, 'info@laevo.nl', '+31 88 2425 200', 1, 0, 'Segment : Exos dorsaux passifs (Laevo FLEX) pour prévention TMS. Adresse : Spykerstraat 7, 3125 BZ Schiedam. Site : https://www.laevo-exoskeletons.com'),
  ('Hypershell', NULL, 'support@hypershell.tech', NULL, 1, 0, 'Segment : Exos pour randonnée, trail, outdoor (Hypershell X, Pro X, X Ultra). Site : https://eu.hypershell.tech'),
  ('Wandercraft', NULL, 'contact@wandercraft.health', '+33 (0)9 72 58 77 05', 1, 0, 'Segment : Exos de marche auto-équilibrés (Atalante, Calvin-40). Site : https://www.wandercraft.eu'),
  ('Ekso Bionics', NULL, 'hello@eksobionics.com', '+1 (510) 984-1761', 1, 0, 'Segment : Exos de marche (EksoNR, Indego) + exo industriel (Ekso EVO). Adresse : 101 Glacier Point, Suite A, San Rafael, CA 94901. Site : https://eksobionics.com');


CREATE TABLE IF NOT EXISTS supplier_products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  supplier_id INT NOT NULL,
  product_id INT NOT NULL,
  supplier_sku VARCHAR(100) DEFAULT NULL,
  buy_price INT DEFAULT NULL,
  lead_time_days INT DEFAULT 0,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS featured_announcements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  message TEXT,
  link_url VARCHAR(255) DEFAULT NULL,
  product_id INT DEFAULT NULL,
  priority INT DEFAULT 0,
  start_at DATETIME DEFAULT NULL,
  end_at DATETIME DEFAULT NULL,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS guides (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  summary TEXT,
  image VARCHAR(255),
  category VARCHAR(100) DEFAULT 'Guide',
  category_slug VARCHAR(150) DEFAULT NULL,
  tags TEXT,
  published_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO products (slug, name, category, category_slug, tag, price, currency, summary, baseline, brand, availability, type, weight, autonomy, charge, main_image, hero_image, bullets, tags, featured_order)
VALUES
  ('exolift', 'ExoLift', 'Exosquelette industriel', 'industriel', 'Industriel', 4500, 'EUR', 'Assistance au levage jusqu’à 30 kg · Batterie échangeable', 'Réduit la contrainte lombaire et améliore la cadence sans fatigue.', 'Exoleton', 'https://schema.org/InStock', 'Actif', '7,8 kg', '6 h', '30 kg', 'assets/img/produit-1.jpg', 'assets/img/produits/exolift-1.jpg', '30 kg assistés|7,8 kg|Autonomie 6 h', 'levage, industriel, batterie 6 h', 1),
  ('atalante-x', 'Atalante X', 'Exosquelette médical', 'medical', 'Médical', NULL, 'EUR', 'Rééducation de la marche · Usage en établissement', 'Solution de marche assistée pour centres de rééducation.', 'Exoleton', 'https://schema.org/InStock', 'Actif', '≈30 kg', '—', '—', 'assets/img/produit-2.jpg', 'assets/img/produits/atalante.jpg', 'Rééducation de la marche|Usage en établissement|Support clinique', 'rééducation, marche, usage clinique', 2),
  ('assistarm', 'AssistArm', 'Exosquelette particulier', 'particulier', 'Particulier', 2800, 'EUR', 'Soulagement des efforts répétés · Ultra-léger', 'Soulage les efforts répétés du haut du corps pour le quotidien.', 'Exoleton', 'https://schema.org/InStock', 'Passif', '2,1 kg', '∞', '—', 'assets/img/produit-3.jpg', 'assets/img/produits/assistarm.jpg', 'Passif, ultra-léger|Soulage les efforts répétés|Idéal pour le quotidien', 'quotidien, léger, passif', 3);

SET @exolift_id = (SELECT id FROM products WHERE slug='exolift');
SET @atalante_id = (SELECT id FROM products WHERE slug='atalante-x');
SET @assistarm_id = (SELECT id FROM products WHERE slug='assistarm');

INSERT INTO product_images (product_id, url, sort_order) VALUES
(@exolift_id, 'assets/img/produits/exolift-1.jpg', 1),
(@exolift_id, 'assets/img/produits/exolift-2.jpg', 2),
(@exolift_id, 'assets/img/produits/exolift-3.jpg', 3),
(@atalante_id, 'assets/img/produits/atalante.jpg', 1),
(@assistarm_id, 'assets/img/produits/assistarm.jpg', 1);

INSERT INTO product_downloads (product_id, label, href, sort_order) VALUES
(@exolift_id, 'Fiche produit (PDF)', 'assets/docs/exolift-fiche.pdf', 1),
(@exolift_id, 'Manuel d’utilisation (PDF)', 'assets/docs/exolift-manuel.pdf', 2),
(@exolift_id, 'Fiche sécurité (PDF)', 'assets/docs/exolift-securite.pdf', 3);

INSERT INTO product_use_cases (product_id, title, kpi, description, sort_order) VALUES
(@exolift_id, 'Logistique', '–28% TMS dos', 'Aide au soulèvement et à la manutention répétée.', 1),
(@exolift_id, 'Industrie', '+18% cadence', 'Maintien de la performance en fin de poste.', 2),
(@exolift_id, 'BTP', '–35% fatigue perçue', 'Postures contraignantes mieux supportées.', 3);

INSERT INTO product_specs (product_id, label, value, sort_order) VALUES
(@exolift_id, 'Type', 'Actif (électrique)', 1),
(@exolift_id, 'Zones assistées', 'Dos / Membres supérieurs', 2),
(@exolift_id, 'Charge assistée', 'Jusqu’à 30 kg', 3),
(@exolift_id, 'Poids', '7,8 kg', 4),
(@exolift_id, 'Autonomie', '≈ 6 h (batterie échangeable)', 5),
(@exolift_id, 'Niveaux d’assistance', '3', 6),
(@exolift_id, 'Taille opérateur', '160–195 cm (S–L)', 7),
(@exolift_id, 'Niveau sonore', '≤ 45 dB', 8),
(@exolift_id, 'Indice de protection', 'IP54', 9),
(@exolift_id, 'Conformité', 'CE, Directive Machines', 10),
(@exolift_id, 'Entretien', 'Module batterie remplaçable, harnais lavable', 11),
(@exolift_id, 'Garantie', '24 mois', 12);

INSERT INTO product_alternatives (product_id, alt_name, alt_slug, tag, summary, price, image, sort_order) VALUES
(@exolift_id, 'AssistArm', 'assistarm', 'Particulier', 'Passif, ultra-léger', 2800, 'assets/img/produits/assistarm.jpg', 1),
(@exolift_id, 'Atalante X', 'atalante-x', 'Médical', 'Rééducation marche', NULL, 'assets/img/produits/atalante.jpg', 2);

INSERT INTO guides (title, summary, image, category, tags, published_at) VALUES
('Choisir un exosquelette pour la logistique', 'Critères essentiels, ROI, prévention des TMS, sécurité et formation.', 'assets/img/hero-exosquelette.jpg', 'Guide', 'logistique, industriel', NOW()),
('Aide à la marche : quelles solutions ?', 'Panorama des dispositifs disponibles et indications d’usage.', 'assets/img/hero-exosquelette.jpg', 'Guide', 'rééducation, marche', NOW()),
('Financements & subventions', 'Pistes pour entreprises, hôpitaux, collectivités et particuliers.', 'assets/img/hero-exosquelette.jpg', 'Guide', 'budget, aides', NOW()),
('Guide pratique : financer son exosquelette', 'Panorama des aides, subventions et démarches pour obtenir un financement.', 'assets/img/hero-exosquelette.jpg', 'Guide', 'financement, budget', NOW());
