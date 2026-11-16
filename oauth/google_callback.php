<?php
require_once __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/social_login.php';
$config = (require __DIR__ . '/../includes/oauth_config.php')['google'];

// Sends the user back to the login page with a message
function fail(string $message): never
{
    $_SESSION['oauth_error'] = $message;
    header('Location: /music_player/login.php');
    exit;
}

// They clicked "Cancel" on Google's screen
if (isset($_GET['error'])) {
    fail('Google sign-in was cancelled.');
}

// The state must match the one google_start.php saved (one use only)
$expectedState = $_SESSION['oauth_state'] ?? '';
unset($_SESSION['oauth_state']);
if ($expectedState === '' || !hash_equals($expectedState, $_GET['state'] ?? '')) {
    fail('Your sign-in expired. Please try again.');
}

try {
    // Trade the one-time code for an access token (server to server, includes our secret)
    $token = oauth_request('https://oauth2.googleapis.com/token', [
        'code'          => $_GET['code'] ?? '',
        'client_id'     => $config['client_id'],
        'client_secret' => $config['client_secret'],
        'redirect_uri'  => $config['redirect_uri'],
        'grant_type'    => 'authorization_code',
    ]);

    // Use the token to ask Google who this is
    $profile = oauth_request('https://openidconnect.googleapis.com/v1/userinfo', null, $token['access_token']);

    if (empty($profile['sub']) || empty($profile['email'])) {
        fail('Google did not share an email address.');
    }

    social_login(
        $pdo,
        'google',
        $profile['sub'],                             // Google's permanent ID for this person
        $profile['email'],
        ($profile['email_verified'] ?? false) === true,
        $profile['given_name'] ?? ($profile['name'] ?? 'Google user'),
        $profile['family_name'] ?? ''
    );
} catch (RuntimeException $e) {
    fail($e->getMessage());
}

header('Location: /music_player/welcome.php');
exit;