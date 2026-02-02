<?php
require_once __DIR__ . '/config/database.php';

header('Content-Type: application/json; charset=utf-8');

$query = trim((string) ($_GET['q'] ?? ''));
$length = function_exists('mb_strlen') ? mb_strlen($query) : strlen($query);
if ($length < 2) {
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