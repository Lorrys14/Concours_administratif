<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost';
$db   = 'concours_admin';
$user = 'root';
$pass = '';
$dsn  = "mysql:host=$host;dbname=$db;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    // Migration automatique : ajout de la colonne photo si elle n'existe pas encore
    try {
        $pdo->exec("ALTER TABLE candidatures ADD COLUMN photo VARCHAR(255) NULL AFTER centre");
    } catch (PDOException $ex) {
        // La colonne existe déjà, ignorer
    }

    // Migration automatique : colonnes pour la gestion variable/dynamique des places
    try {
        $pdo->exec("ALTER TABLE concours ADD COLUMN mode_places ENUM('fixe', 'pourcentage', 'ratio') DEFAULT 'pourcentage' AFTER frais");
    } catch (PDOException $ex) {}
    try {
        $pdo->exec("ALTER TABLE concours ADD COLUMN pourcentage_places INT DEFAULT 10 AFTER mode_places");
    } catch (PDOException $ex) {}
    try {
        $pdo->exec("ALTER TABLE concours ADD COLUMN ratio_places INT DEFAULT 5 AFTER pourcentage_places");
    } catch (PDOException $ex) {}
    try {
        $pdo->exec("ALTER TABLE concours ADD COLUMN places_min INT DEFAULT 1 AFTER ratio_places");
    } catch (PDOException $ex) {}
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . htmlspecialchars($e->getMessage()));
}

// Définition de la URL de base dynamique
if (!defined('BASE_URL')) {
    $projectDir = str_replace('\\', '/', dirname(__DIR__));
    $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');

    if (!empty($docRoot) && strpos($projectDir, $docRoot) === 0) {
        $relative = substr($projectDir, strlen($docRoot));
        $baseUrl = '/' . ltrim($relative, '/');
    } else {
        $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $baseDir = rtrim(str_replace('\\', '/', $scriptDir), '/');
        if (preg_match('#/(admin|candidat|agent|api)$#i', $baseDir)) {
            $baseDir = dirname($baseDir);
        }
        $baseUrl = $baseDir;
    }
    define('BASE_URL', rtrim($baseUrl, '/') . '/');
}

/**
 * Calcule le nombre de places restantes pour un concours.
 *
 * @param array $concours Données du concours
 * @param PDO|null $pdo Instance PDO optionnelle
 * @param int|null $totalInscriptions Nombre total d'inscriptions optionnel
 * @return int Nombre de places disponibles
 */
if (!function_exists('calculer_places_concours')) {
    function calculer_places_concours($concours, $pdo = null, $totalInscriptions = null) {
        if (!$concours) return 0;
        return max(0, (int)($concours['places'] ?? 0));
    }
}

/**
 * Libellé explicatif du nombre de places disponibles.
 */
if (!function_exists('libelle_regle_places')) {
    function libelle_regle_places($concours, $calculatedPlaces = null) {
        if (!$concours) return '';
        if ($calculatedPlaces === null) {
            $calculatedPlaces = max(0, (int)($concours['places'] ?? 0));
        }
        return "$calculatedPlaces place(s) disponible(s)";
    }
}

/**
 * Vérifie et ferme automatiquement les concours dont la date limite d'inscription est dépassée.
 * Rejette automatiquement les candidatures incomplètes à la date limite (sans incrémenter le nombre de places).
 *
 * @param PDO $pdo
 */
if (!function_exists('verifier_fermeture_concours')) {
    function verifier_fermeture_concours($pdo) {
        if (!$pdo) return;
        try {
            $today = date('Y-m-d');

            // 1. Fermer automatiquement les concours dont la date_fin est strictement dépassée
            $stClose = $pdo->prepare("UPDATE concours SET statut = 'ferme' WHERE statut = 'ouvert' AND date_fin < ?");
            $stClose->execute([$today]);

            // 2. Rejeter automatiquement les candidatures non soumises (brouillon)
            $stRejectDrafts = $pdo->prepare("
                UPDATE candidatures c
                JOIN concours co ON co.id = c.concours_id
                SET c.statut = 'rejete',
                    c.motif_rejet = 'Dossier incomplet : Non soumis avant la date limite d\'inscription'
                WHERE (co.date_fin < ? OR co.statut = 'ferme')
                  AND c.statut = 'brouillon'
            ");
            $stRejectDrafts->execute([$today]);

            // 3. Rejeter automatiquement les candidatures aux pièces manquantes (photo ou documents justificatifs)
            $stIncomplete = $pdo->prepare("
                SELECT c.id, c.user_id, c.numero_candidat, c.photo, co.titre as concours_titre,
                       (SELECT COUNT(*) FROM documents d WHERE d.candidature_id = c.id) as nb_docs
                FROM candidatures c
                JOIN concours co ON co.id = c.concours_id
                WHERE (co.date_fin < ? OR co.statut = 'ferme')
                  AND c.statut IN ('soumis', 'en_verification')
            ");
            $stIncomplete->execute([$today]);
            $rows = $stIncomplete->fetchAll();

            foreach ($rows as $cand) {
                if (empty($cand['photo']) || (int)$cand['nb_docs'] < 4) {
                    $stUp = $pdo->prepare("UPDATE candidatures SET statut = 'rejete', motif_rejet = 'Dossier incomplet à la date limite (quittance ou pièces manquantes)' WHERE id = ?");
                    $stUp->execute([$cand['id']]);

                    $stNotif = $pdo->prepare("INSERT INTO notifications (user_id, titre, message) VALUES (?, ?, ?)");
                    $stNotif->execute([
                        $cand['user_id'],
                        'Notification',
                        "Votre candidature est rejetée. Motif : Dossier incomplet à la date limite."
                    ]);
                }
            }
        } catch (Exception $ex) {
            // Ignorer silencieusement pour ne pas bloquer l'application
        }
    }
}

// Exécution automatique des contrôles de fermeture et rejet
verifier_fermeture_concours($pdo);

