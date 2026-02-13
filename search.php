<?php
require_once __DIR__ . '/config/database.php';

header('Content-Type: application/json; charset=utf-8');

$query = trim((string) ($_GET['q'] ?? ''));
$isDebug = isset($_GET['debug']);

if ($isDebug) {
    try {
        $database = new Database();
        $debugQuery = trim((string) ($_GET['q'] ?? ''));
        echo json_encode([
            'query' => $debugQuery,
            'length' => function_exists('mb_strlen') ? mb_strlen($debugQuery) : strlen($debugQuery),
            'count' => $database->fetchDeviceCount(),
            'sample' => $database->fetchDeviceSample(),
            'diagnostics' => $debugQuery !== '' ? $database->fetchSearchDiagnostics($debugQuery) : null,
            'results' => $debugQuery !== '' ? $database->fetchSearchSuggestions($debugQuery) : [],
        ]);
    } catch (Throwable $exception) {
        echo json_encode(['error' => $exception->getMessage()]);
    }
    exit;
}

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
