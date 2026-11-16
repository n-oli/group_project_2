<?php
// Copy this file to oauth_config.php and fill in the real values.
// oauth_config.php is listed in .gitignore: never commit real secrets.

return [
    'google' => [
        'client_id'     => 'PASTE-CLIENT-ID.apps.googleusercontent.com',
        'client_secret' => 'PASTE-CLIENT-SECRET',
        'redirect_uri'  => 'http://localhost/music_player/oauth/google_callback.php',
    ],
];