<?php
require_once __DIR__ . '/../includes/auth.php';
$config = (require __DIR__ . '/../includes/oauth_config.php')['google'];

// Random value we check when Google sends the user back (stops forged logins)
$_SESSION['oauth_state'] = bin2hex(random_bytes(16));

$params = [
    'client_id'     => $config['client_id'],
    'redirect_uri'  => $config['redirect_uri'],
    'response_type' => 'code',
    'scope'         => 'openid email profile',
    'state'         => $_SESSION['oauth_state'],
    'prompt'        => 'select_account',
];
header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params));
exit;