<?php
require_once __DIR__ . '/models/Auth.php';

Auth::logout();
header('Location: index.php');
exit;
