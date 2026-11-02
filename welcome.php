<?php
require __DIR__ . '/includes/auth.php';
$user = require_user();
redirect_to(dashboard_path($user['role_type']));
