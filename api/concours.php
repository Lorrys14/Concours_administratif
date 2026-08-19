<?php
header('Content-Type: application/json; charset=utf-8');
require '../config/database.php';

try {
    $st = $pdo->query("SELECT * FROM concours ORDER BY date_debut DESC");
    $data = $st->fetchAll();
    echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
