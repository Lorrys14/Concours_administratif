<?php
header('Content-Type: application/json; charset=utf-8');
require '../config/database.php';

try {
    $concoursId = (int)($_GET['concours_id'] ?? 0);
    if ($concoursId > 0) {
        $st = $pdo->prepare("SELECT c.numero_candidat, u.nom, u.prenom, r.moyenne, r.rang, r.decision 
                             FROM resultats r 
                             JOIN candidatures c ON c.id = r.candidature_id 
                             JOIN users u ON u.id = c.user_id 
                             WHERE c.concours_id = ? AND r.publie = 1 
                             ORDER BY r.rang ASC");
        $st->execute([$concoursId]);
    } else {
        $st = $pdo->query("SELECT c.numero_candidat, u.nom, u.prenom, co.titre as concours, r.moyenne, r.rang, r.decision 
                           FROM resultats r 
                           JOIN candidatures c ON c.id = r.candidature_id 
                           JOIN users u ON u.id = c.user_id 
                           JOIN concours co ON co.id = c.concours_id 
                           WHERE r.publie = 1 
                           ORDER BY co.id, r.rang ASC");
    }
    $data = $st->fetchAll();
    echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
