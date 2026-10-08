<?php
/**
 * config.php - loaded by every page.
 * Starts the session, connects to MySQL and holds small helper functions.
 */
session_start();

// ---- Database settings (change these to match your machine) ----
const DB_HOST = 'localhost';
const DB_NAME = 'student_portal';
const DB_USER = 'root';
const DB_PASS = '';

// ---- Upload settings ----
const UPLOAD_DIR  = __DIR__ . '/uploads/';
const MAX_PDF_MB  = 2;

/** Returns one shared PDO connection (prepared statements = no SQL injection). */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

/** Escape text before printing it in HTML (prevents XSS). */
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/** One-time message shown on the next page (success / danger / info). */
function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function show_flash(): void
{
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        echo '<div class="alert alert-' . e($f['type']) . ' alert-dismissible fade show">'
           . e($f['message']) . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        unset($_SESSION['flash']);
    }
}

/** Simple CSRF protection for POST forms. */
function csrf_field(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">';
}

function csrf_check(): void
{
    if (empty($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) {
        http_response_code(400);
        exit('Invalid request token. Please go back and try again.');
    }
}

/** Display ID such as STU001 (matches the mockup). */
function student_code(int $id): string
{
    return 'STU' . str_pad((string)$id, 3, '0', STR_PAD_LEFT);
}

function calc_age(?string $dob): int
{
    return $dob ? (new DateTime($dob))->diff(new DateTime())->y : 0;
}
