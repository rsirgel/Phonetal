<?php
require_once __DIR__ . '/models/auth.php';
require_once __DIR__ . '/models/google-auth.php';

Auth::init();

if (!GoogleAuth::isConfigured()) {
    header('Location: login.php?auth_error=google_not_configured');
    exit;
}

$expectedState = (string) ($_SESSION['google_oauth_state'] ?? '');
$expiresAt = (int) ($_SESSION['google_oauth_state_expires'] ?? 0);
unset($_SESSION['google_oauth_state'], $_SESSION['google_oauth_state_expires']);

$state = (string) ($_GET['state'] ?? '');
$code = (string) ($_GET['code'] ?? '');

if ($expectedState === '' || $expiresAt < time() || !hash_equals($expectedState, $state)) {
    header('Location: login.php?auth_error=google_state');
    exit;
}

if ($code === '') {
    header('Location: login.php?auth_error=google_code');
    exit;
}

$googleUser = GoogleAuth::fetchUserByCode($code);
if (!is_array($googleUser)) {
    header('Location: login.php?auth_error=google_failed');
    exit;
}

$email = strtolower(trim((string) ($googleUser['email'] ?? '')));
$emailVerified = !empty($googleUser['email_verified']);

if ($email === '' || !$emailVerified) {
    header('Location: login.php?auth_error=google_email');
    exit;
}

if (!Auth::loginExistingUserByEmail($email)) {
    header('Location: login.php?auth_error=google_no_account');
    exit;
}

header('Location: kosik.php');
exit;
