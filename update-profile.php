<?php

require_once __DIR__ . '/models/Auth.php';
require_once __DIR__ . '/config/database.php';

Auth::init();
$user = Auth::user();

header('Content-Type: application/json; charset=utf-8');

if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Neprihlásený používateľ.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Nepovolená metóda.']);
    exit;
}

$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!Auth::validateCsrf($csrfToken)) {
    http_response_code(403);
    echo json_encode(['error' => 'Neplatný bezpečnostný token.']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['error' => 'Neplatné dáta.']);
    exit;
}

$field = $payload['field'] ?? '';
$value = $payload['value'] ?? '';

$allowedFields = [
    'first_name' => 'meno',
    'last_name' => 'priezvisko',
    'email' => 'email',
    'phone' => 'telefon',
    'city' => 'mesto',
    'street' => 'ulica',
    'postal_code' => 'psc',
    'iban' => 'iban',
    'bic' => 'bic',
    'account_owner' => 'meno_uctu',
];

if (!isset($allowedFields[$field])) {
    http_response_code(400);
    echo json_encode(['error' => 'Neplatné pole.']);
    exit;
}

$normalizedValue = is_string($value) ? trim($value) : '';
if ($field === 'email' && $normalizedValue !== '' && !filter_var($normalizedValue, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Neplatný email.']);
    exit;
}

$database = new Database();
$database->updateUserFields($user['id'], [
    $allowedFields[$field] => $normalizedValue === '' ? null : $normalizedValue,
]);

$_SESSION['user'][$field] = $normalizedValue === '' ? null : $normalizedValue;
if ($field === 'first_name' || $field === 'last_name') {
    $firstName = $_SESSION['user']['first_name'] ?? '';
    $lastName = $_SESSION['user']['last_name'] ?? '';
    $_SESSION['user']['name'] = trim($firstName . ' ' . $lastName);
}

echo json_encode(['success' => true]);
