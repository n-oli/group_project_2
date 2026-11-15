<?php
/**
 * welcome.php - the "traffic controller".
 * - Not logged in -> require_user() sends you to login.php.
 * - Logged in     -> forwarded to your role's home page
 * (admin/index.php or listener/index.php).
 * Link here whenever you want "take the user to their home page".
 */
require __DIR__ . '/includes/auth.php';
$user = require_user();
redirect_to(dashboard_path($user['role_type']));
