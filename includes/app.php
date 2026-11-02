<?php
// Shared session, links, and forms; supports installation in a URL subfolder.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$appDirectory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$entryDirectory = realpath(dirname($_SERVER['SCRIPT_FILENAME']));
if (in_array($entryDirectory, [realpath(__DIR__ . '/../admin'), realpath(__DIR__ . '/../listener')], true)) {
    $appDirectory = dirname($appDirectory);
}
define('APP_URL', rtrim(str_replace('\\', '/', $appDirectory), '/.'));

function app_url($path = '')
{
    return APP_URL . '/' . ltrim($path, '/');
}

function escape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect_to($path)
{
    header('Location: ' . app_url($path));
    exit;
}

function dashboard_path($role)
{
    return $role === 'admin' ? 'admin/index.php' : 'listener/index.php';
}

function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function valid_csrf_token()
{
    $token = $_POST['csrf_token'] ?? '';
    return is_string($token) && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}
