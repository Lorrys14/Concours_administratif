CREATE DATABASE IF NOT EXISTS concours_admin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE concours_admin;

-- Désactivation temporaire des contraintes de clés étrangères pour permettre la suppression sans erreur
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS resultats;
DROP TABLE IF EXISTS notes;
DROP TABLE IF EXISTS epreuves;
DROP TABLE IF EXISTS documents;
DROP TABLE IF EXISTS candidatures;
DROP TABLE IF EXISTS centres;
DROP TABLE IF EXISTS concours;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- Table `users`
-- --------------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    date_naissance DATE NULL,
    lieu_naissance VARCHAR(150) NULL,
    sexe ENUM('M', 'F') DEFAULT 'M',
    nationalite VARCHAR(100) DEFAULT 'Ivoirienne',
    email VARCHAR(150) UNIQUE NOT NULL,
    telephone VARCHAR(30) NULL,
    adresse VARCHAR(255) NULL,
    ville VARCHAR(100) NULL,
    niveau_etude VARCHAR(100) NULL,
    diplome_obtenu VARCHAR(150) NULL,
    etablissement VARCHAR(150) NULL,
    annee_obtention YEAR NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'candidat', 'agent') NOT NULL DEFAULT 'candidat',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table `concours`
-- --------------------------------------------------------
CREATE TABLE concours (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(200) NOT NULL,
    session VARCHAR(20) NOT NULL,
    description TEXT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    age_min INT DEFAULT 18,
    age_max INT DEFAULT 35,
    diplome_requis VARCHAR(200) NULL,
    frais INT DEFAULT 0,
    places INT DEFAULT 0,
    statut ENUM('brouillon', 'ouvert', 'ferme', 'publie') DEFAULT 'brouillon',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table `centres`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS centres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(150) NOT NULL,
    ville VARCHAR(100) NOT NULL,
    adresse VARCHAR(255) NULL,
    capacite INT DEFAULT 500,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table `candidatures`
-- --------------------------------------------------------
CREATE TABLE candidatures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    concours_id INT NOT NULL,
    numero_candidat VARCHAR(40) UNIQUE NOT NULL,
    statut ENUM('brouillon', 'soumis', 'en_verification', 'valide', 'rejete', 'convoque', 'admis', 'non_admis') DEFAULT 'brouillon',
    centre VARCHAR(150) NULL,
    photo VARCHAR(255) NULL,
    date_soumission DATETIME NULL,
    motif_rejet TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_user_id (user_id),
    KEY idx_concours_id (concours_id),
    CONSTRAINT fk_candidatures_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_candidatures_concours FOREIGN KEY (concours_id) REFERENCES concours(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table `documents`
-- --------------------------------------------------------
CREATE TABLE documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    candidature_id INT NOT NULL,
    type_document VARCHAR(100) NOT NULL,
    fichier VARCHAR(255) NOT NULL,
    statut ENUM('en_attente', 'valide', 'rejete') DEFAULT 'en_attente',
    KEY idx_candidature_id (candidature_id),
    CONSTRAINT fk_documents_candidature FOREIGN KEY (candidature_id) REFERENCES candidatures(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table `epreuves`
-- --------------------------------------------------------
CREATE TABLE epreuves (
    id INT AUTO_INCREMENT PRIMARY KEY,
    concours_id INT NOT NULL,
    nom VARCHAR(150) NOT NULL,
    coefficient DECIMAL(5,2) DEFAULT 1.00,
    KEY idx_concours_id (concours_id),
    CONSTRAINT fk_epreuves_concours FOREIGN KEY (concours_id) REFERENCES concours(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table `notes`
-- --------------------------------------------------------
CREATE TABLE notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    candidature_id INT NOT NULL,
    epreuve_id INT NOT NULL,
    note DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    UNIQUE KEY unique_candidature_epreuve (candidature_id, epreuve_id),
    KEY idx_candidature_id (candidature_id),
    KEY idx_epreuve_id (epreuve_id),
    CONSTRAINT fk_notes_candidature FOREIGN KEY (candidature_id) REFERENCES candidatures(id) ON DELETE CASCADE,
    CONSTRAINT fk_notes_epreuve FOREIGN KEY (epreuve_id) REFERENCES epreuves(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table `resultats`
-- --------------------------------------------------------
CREATE TABLE resultats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    candidature_id INT UNIQUE NOT NULL,
    moyenne DECIMAL(5,2) NOT NULL,
    rang INT NULL,
    decision ENUM('admis', 'liste_attente', 'non_admis') NOT NULL,
    publie TINYINT(1) DEFAULT 0,
    CONSTRAINT fk_resultats_candidature FOREIGN KEY (candidature_id) REFERENCES candidatures(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table `notifications`
-- --------------------------------------------------------
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    titre VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    lu TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_user_id (user_id),
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Données de démonstration
-- Mot de passe pour tous les comptes : "admin123"
-- --------------------------------------------------------
INSERT INTO users (nom, prenom, email, telephone, password, role, date_naissance, lieu_naissance, sexe, nationalite, adresse, ville, niveau_etude, diplome_obtenu) VALUES
('ADMIN', 'Administrateur', 'admin@gmail.com', '0700000000', '$2y$10$2kYay2hJiGIwtkqtpxY5w.o7Oha64QVqVPit63oWI4uE7nvq3wAm.', 'admin', '1985-05-15', 'Abidjan', 'M', 'Ivoirienne', 'Plateau', 'Abidjan', 'Master', 'Master en Management'),
('KOUASSI', 'Jean', 'candidat@gmail.com', '0700000001', '$2y$10$2kYay2hJiGIwtkqtpxY5w.o7Oha64QVqVPit63oWI4uE7nvq3wAm.', 'candidat', '1998-08-20', 'Cocody', 'M', 'Ivoirienne', 'Riviera 2', 'Abidjan', 'Licence', 'Licence en Informatique'),
('AGENT', 'Agent de gestion', 'agent@gmail.com', '0700000002', '$2y$10$2kYay2hJiGIwtkqtpxY5w.o7Oha64QVqVPit63oWI4uE7nvq3wAm.', 'agent', '1990-12-10', 'Yamoussoukro', 'M', 'Ivoirienne', 'Centre', 'Yamoussoukro', 'Licence', 'Licence Admin');

INSERT INTO centres (nom, ville, adresse, capacite) VALUES
('Lycée Sainte Marie', 'Abidjan - Cocody', 'Boulevard de France', 500),
('Lycée Garçons', 'Yamoussoukro', 'Quartier Habitat', 400),
('Lycée Classique', 'Bouaké', 'Quartier Commerce', 350),
('Lycée Moderne', 'Korhogo', 'Centre-ville', 300);

INSERT INTO concours (titre, session, description, date_debut, date_fin, age_min, age_max, diplome_requis, frais, places, statut) VALUES
('Concours d\'Administrateur', '2026', 'Concours d\'accès aux emplois administratifs.', '2026-09-01', '2026-09-30', 18, 35, 'Licence', 10000, 100, 'ouvert'),
('Concours de Technicien', '2026', 'Concours de recrutement des techniciens.', '2026-09-05', '2026-10-05', 18, 35, 'BTS', 5000, 80, 'ouvert');

INSERT INTO epreuves (concours_id, nom, coefficient) VALUES
(1, 'Culture générale', 2.00),
(1, 'Français', 2.00),
(1, 'Informatique', 3.00),
(1, 'Entretien', 2.00),
(2, 'Technique', 3.00),
(2, 'Français', 2.00);

