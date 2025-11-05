<?php
require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/../Route.php';
require_once __DIR__ . '/../web.php';
require_once __DIR__ . '/../Utilities/functions.php'; 


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Retrieve session data (set from LoginController)
$fullName = $_SESSION['fullName'] ?? 'Guest';
$role = $_SESSION['role'] ?? 'User';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (!isset($_SESSION['last_page'])) {
    $_SESSION['last_page'] = '';
}

$_SESSION['previous_page'] = $_SESSION['last_page'];
$_SESSION['last_page'] = $uri;

// // if ($uri == '/profile/create') {
// // } else if ($uri !== '/login' && $uri !== '/login/process' && empty($_SESSION['profile']['profileID'])) {
// if ($uri !== '/login' && $uri !== '/login/process' && $uri !== '/profile/create' && $uri !== '/api/validate-profile' && empty($_SESSION['profile_id'])) {
//     header('Location: /login');
//     exit;
// }

Route::dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);