<?php
/**
 * includes/app.php - the small toolbox every page loads first.
 *
 * WHAT IT GIVES YOU
 * - A started session (so $_SESSION works on every page).
 * - APP_URL and app_url(): build links that work no matter which folder the
 * project lives in (htdocs/decibel, htdocs/music_player, or the site root).
 * - escape(): make text safe to print inside HTML.
 * - redirect_to(): send the browser to another page and stop.
 * - dashboard_path(): which home page a role should land on.
 * - csrf_token() / valid_csrf_token(): protect forms from being submitted
 * by another website.
 *
 * HOW IT IS LOADED
 * You normally do not include this file directly. includes/auth.php and
 * includes/header.php both require it, so any page that includes one of
 * those already has everything in here.
 */

// Shared session, links, and forms; supports installation in a URL subfolder.
// --- 1. Session -----------------------------------------------------------
// A session is how PHP remembers who you are between page loads (it stores a
// cookie in the browser and keeps the matching data on the server).
// session_start() must only run once per request, so we check first.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- 2. Work out the base URL of the app -------------------------------------
// Problem: on one laptop the site is at http://localhost/decibel/, on another
// it is at http://localhost/group_project_2/. Hardcoding either breaks the other.
// Solution: look at the URL of the script that is running right now and take
// its folder. Example: SCRIPT_NAME "/decibel/login.php" -> folder "/decibel".
// (str_replace turns Windows back-slashes into forward slashes.)
$appDirectory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));

// Pages inside admin/ or listener/ are one folder deeper
// ("/decibel/admin/index.php" -> "/decibel/admin"), so for those we go up one
// level to get back to the app's root folder.
// NOTE: if you add another sub-folder of pages (e.g. player/), add it to the
// list below or links on those pages will point to the wrong place.
$entryDirectory = realpath(dirname($_SERVER['SCRIPT_FILENAME']));
if (in_array($entryDirectory, [realpath(__DIR__ . '/../admin'), realpath(__DIR__ . '/../listener')], true)) {
    $appDirectory = dirname($appDirectory);
}

// APP_URL ends up as "/decibel" (or "" when the app is at the site root).
// rtrim removes a trailing "/" or "." so we never produce "//login.php".
define('APP_URL', rtrim(str_replace('\\', '/', $appDirectory), '/.'));

// Build a link to a file in the app.
// app_url('login.php')        -> "/decibel/login.php"
// app_url('admin/index.php')  -> "/decibel/admin/index.php"
// Always use this for href, action and src instead of typing the path by hand.
function app_url($path = '')
{
    return APP_URL . '/' . ltrim($path, '/');
}

// Make a value safe to print inside HTML.
// It turns characters like < > " ' into harmless codes, so if someone registers
// with the name <script>alert(1)</script> it is shown as text, not run as code.
// (This kind of attack is called XSS.) Use it on EVERYTHING that came from a
// user or from the database:  <?= escape($user['first_name']) [close tag]
function escape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Send the browser to another page of the app and stop this script.
// The "exit" matters: without it PHP would keep running the rest of the page
// even though the visitor is being sent elsewhere.
function redirect_to($path)
{
    header('Location: ' . app_url($path));
    exit;
}

// Where should this role land after logging in?
// admin -> admin/index.php, everyone else -> listener/index.php.
function dashboard_path($role)
{
    return $role === 'admin' ? 'admin/index.php' : 'listener/index.php';
}

// --- 3. CSRF protection ------------------------------------------------------
// CSRF = another website secretly submitting a form to OUR site while you are
// logged in (your browser sends your login cookie automatically).
// The defence: every form carries a secret random value that only our pages
// know. A form without the right value is rejected.
//
// csrf_token() returns that secret, creating it the first time it is needed.
// Put it in every POST form:
// <input type="hidden" name="csrf_token" value="(print escape(csrf_token()) here)">
function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Check the secret that came back with a submitted form.
// Returns true only when the form's token matches the one saved in the session.
// hash_equals() compares the two safely (it takes the same time whether they
// match or not, so an attacker cannot guess the token piece by piece).
// Call this at the top of every POST handler.
function valid_csrf_token()
{
    $token = $_POST['csrf_token'] ?? '';
    return is_string($token) && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}
