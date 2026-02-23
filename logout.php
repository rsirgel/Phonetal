<?php
require_once __DIR__ . '/models/auth.php';

Auth::logout();
header('Location: index.php');
exit;
