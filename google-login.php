<?php
require_once __DIR__ . '/models/auth.php';
require_once __DIR__ . '/models/google-auth.php';

Auth::init();

if (!GoogleAuth::isConfigured()) {
    header('Location: login.php?auth_error=google_not_configured');
    exit;
}

$state = bin2hex(random_bytes(32));
$_SESSION['google_oauth_state'] = $state;
$_SESSION['google_oauth_state_expires'] = time() + 600;

header('Location: ' . GoogleAuth::buildAuthUrl($state));
exit;
