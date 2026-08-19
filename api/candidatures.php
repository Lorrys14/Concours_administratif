<?php
header('Content-Type: application/json; charset=utf-8');
require '../config/database.php';

try {
    $st = $pdo->query("SELECT c.id, c.numero_candidat, c.statut, c.centre, c.created_at, u.nom, u.prenom, co.titre as concours 
                       FROM candidatures c 
                       JOIN users u ON u.id = c.user_id 
                       JOIN concours co ON co.id = c.concours_id 
                       ORDER BY c.id DESC");
    $data = $st->fetchAll();
    echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
