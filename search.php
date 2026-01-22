<?php
require_once __DIR__ . '/database/database.php';

header('Content-Type: application/json; charset=utf-8');

$query = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($query) < 3) {
    echo json_encode([]);
    exit;
}

try {
    $database = new Database();
    $results = $database->fetchSearchSuggestions($query);
    echo json_encode($results);
} catch (Throwable $exception) {
    echo json_encode([]);
}
