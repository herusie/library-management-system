<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Manila');

const MAX_ACTIVE_LOANS = 3;  // books one student can hold at once
const LOAN_DAYS = 7;         // loan length and extension length, in days
const MAX_EXTENSIONS = 1;    // extensions allowed per loan
const LIBRARY_NAME  = 'JRU Library';
const DUE_SOON_DAYS = 3;

session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
$db_name = getenv('DB_NAME') ?: 'library';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    error_log('Database connection failed: ' . $conn->connect_error);
    http_response_code(500);
    exit('The application could not connect to its database.');
}

$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '+08:00'");

function require_login(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}