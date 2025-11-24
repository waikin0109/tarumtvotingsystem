<?php
require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/../Route.php';
require_once __DIR__ . '/../web.php';
require_once __DIR__ . '/../Utilities/functions.php'; 
require_once __DIR__ . '/../vendor/autoload.php';   //newly added for report

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Retrieve session data (set from LoginController)
$accountLoggedInId = $_SESSION['accountID'] ?? '';
$fullName = $_SESSION['fullName'] ?? 'Guest';
$role = $_SESSION['role'] ?? 'User';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($uri === '' || $uri === false) { $uri = '/'; } // <-- ADDED

if (!isset($_SESSION['last_page'])) {
    $_SESSION['last_page'] = '';
}

$_SESSION['previous_page'] = $_SESSION['last_page'];
$_SESSION['last_page'] = $uri;

// ------------------- ADDED: role-based home resolver -------------------
function home_by_role(string $role): string {
    switch (strtoupper($role)) {
        case 'ADMIN':   return '/admin/home';
        case 'NOMINEE': return '/nominee/home';
        case 'STUDENT': 
        default:        return '/student/home';
    }
}
// -----------------------------------------------------------------------

// ------------------- ADDED: redirect logged-in users off /login --------
if (!empty($_SESSION['accountID']) && ($uri === '/login' || $uri === '/login/process')) {
    header('Location: ' . home_by_role($role));
    exit;
}
// -----------------------------------------------------------------------

// ------------------- ADDED: logged-in root -> role home ----------------
if (!empty($_SESSION['accountID']) && $uri === '/') {
    header('Location: ' . home_by_role($role));
    exit;
}
// -----------------------------------------------------------------------

// ------------------- Existing: root -> /login when not logged in -------
if (empty($_SESSION['accountID']) && ($uri === '/' || $uri === '')) {
    header('Location: /login');
    exit;
}
// -----------------------------------------------------------------------

// Existing guard (unchanged)
if ($uri !== '/login' && $uri !== '/login/process' && $uri !== '/profile/create' && $uri !== '/api/validate-profile' && empty($_SESSION['accountID'])) {
    header('Location: /login');
    exit;
}

Route::dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
