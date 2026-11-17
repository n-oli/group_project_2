<?php
/**
 * tests/auth_flow.php - an automatic end-to-end check of the login system.
 *
 * WHAT IT DOES
 * It behaves like a robot visitor: it starts a temporary copy of the site,
 * registers accounts, logs in and out, and tries things that should be
 * blocked. If every check passes it prints PASS; otherwise FAIL plus the reason.
 *
 * HOW TO RUN IT (MySQL/MariaDB must be running, e.g. via XAMPP)
 * php tests/auth_flow.php
 *
 * IS IT SAFE?
 * Yes. It creates its own throw-away database called decibel_test_<random>
 * and deletes it at the end. It never touches the real music_player database.
 *
 * It runs everything twice: once with the app at the site root ("/") and once
 * inside a sub-folder, to prove links work wherever the project is installed.
 * Run it after changing anything in login, register, logout or includes/.
 */

// Run with: php tests/auth_flow.php (MariaDB must be running).
// Uses a fresh test database, never the application's database.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

// Tiny assertion helper: if $condition is false, stop the test with $message.
function check($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

// A pretend web browser. It can request a page (GET) or submit a form (POST)
// and, like a real browser, remembers cookies between requests so the site
// sees it as the same logged-in visitor.
class Browser
{
    public $cookies = [];
    private $base;

    public function __construct($base)
    {
        $this->base = $base;
    }

    // $data === null -> GET the page.  $data is an array -> POST it as a form.
    // Returns ['status' => 200/302/403..., 'body' => the HTML, 'location' => redirect target or null].
    // Redirects are NOT followed automatically, so tests can check where they point.
    public function request($path, $data = null)
    {
        $headers = ['Connection: close'];
        if ($this->cookies) {
            $pairs = [];
            foreach ($this->cookies as $name => $value) {
                $pairs[] = "$name=$value";
            }
            $headers[] = 'Cookie: ' . implode('; ', $pairs);
        }
        if ($data !== null) {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        }
        $context = stream_context_create(['http' => [
            'method' => $data === null ? 'GET' : 'POST',
            'header' => implode("\r\n", $headers),
            'content' => $data === null ? '' : http_build_query($data),
            'follow_location' => 0, 'ignore_errors' => true, 'timeout' => 5,
        ]]);
        $stream = fopen($this->base . $path, 'r', false, $context);
        check($stream !== false, "Could not request $path");
        $body = stream_get_contents($stream);
        $responseHeaders = stream_get_meta_data($stream)['wrapper_data'];
        fclose($stream);
        preg_match('/\s(\d{3})\s/', $responseHeaders[0], $match);
        $result = ['status' => (int) $match[1], 'body' => $body, 'location' => null];
        foreach ($responseHeaders as $header) {
            if (stripos($header, 'Set-Cookie: ') === 0) {
                preg_match('/^Set-Cookie: ([^=]+)=([^;]*)/i', $header, $cookie);
                $this->cookies[$cookie[1]] = $cookie[2];
            }
            if (stripos($header, 'Location: ') === 0) {
                $result['location'] = substr($header, 10);
            }
        }

        // Any PHP error text in a page fails the test, even if the page "worked".
        check(!preg_match('/(?:Fatal error|Warning:|Deprecated:)/', $body), "PHP error on $path: $body");
        return $result;
    }
}

// Pull the hidden CSRF token out of a page's HTML so the robot can send it
// back with the form, exactly like a real browser would.
function token($response)
{
    check(preg_match('/name="csrf_token" value="([a-f0-9]+)"/', $response['body'], $match) === 1,
        'Form is missing CSRF token');
    return $match[1];
}

// Check that a response is a redirect (status 302) to the expected page.
function redirected($response, $location)
{
    check($response['status'] === 302 && $response['location'] === $location,
        'Unexpected redirect: ' . json_encode($response) . ', expected ' . $location);
}

// The actual test scenario. $prefix is '' or '/<folder>' depending on where
// the app is mounted; $suffix keeps usernames different between the two runs.
function exercise($base, $prefix, $db, $suffix)
{
    $browser = new Browser($base);
    $path = fn($page) => $prefix . '/' . $page;

    // 1. Logged-out visitors are sent to the login page from every protected page.
    foreach (['welcome.php', 'profile.php', 'admin/index.php', 'listener/index.php'] as $page) {
        redirected($browser->request($path($page)), $path('login.php'));
    }

    // 2. Registration. The first name is a <script> tag on purpose (to prove
    // output is escaped) and 'role' => 'admin' tries to sneak in an admin
    // account (it must be ignored).
    $form = $browser->request($path('register.php'));
    check($form['status'] === 200, 'Registration form failed');
    if (!is_file(dirname(__DIR__) . '/includes/oauth_config.php')) {
        check(!str_contains($form['body'], 'Sign up with Google'), 'Unconfigured Google sign-up was shown');
    }
    check(str_contains($form['body'], $path('assets/style.css')), 'Incorrect stylesheet URL');
    check($browser->request($path('assets/style.css'))['status'] === 200, 'Stylesheet unavailable');
    $data = ['first_name' => '<script>alert(1)</script>', 'last_name' => 'Tester',
        'email' => "listener$suffix@example.test", 'username' => "listener$suffix",
        'password' => 'Test-password-235', 'confirm_password' => 'Test-password-235',
        'phone' => '203-555-0100', 'state_code' => 'ct', 'post_code' => '06515',
        'role' => 'admin'];
    $countBefore = (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn();

    // 2a. A form without the CSRF token must be refused and save nothing.
    $invalid = $browser->request($path('register.php'), $data);
    check(str_contains($invalid['body'], 'form has expired'), 'Missing registration CSRF was accepted');
    check((int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn() === $countBefore,
        'Invalid form inserted an account');
    $data['csrf_token'] = token($form);

    // 2b. Bad ZIP rejected; correct form succeeds; the same form again is a duplicate.
    $invalidZip = $browser->request($path('register.php'), array_replace($data, ['post_code' => '123']));
    check(str_contains($invalidZip['body'], 'five-digit ZIP'), 'Invalid ZIP was accepted');
    redirected($browser->request($path('register.php'), $data), $path('login.php?registered=1'));
    $duplicate = $browser->request($path('register.php'), $data);
    check(str_contains($duplicate['body'], 'already taken'), 'Duplicate account was accepted');

    // 2c. Look in the database: the password is hashed and the role is listener.
    $query = $db->prepare('SELECT c.password_hash, r.role_type FROM credentials c JOIN users u ON u.user_id=c.user_id JOIN roles r ON r.role_id=u.role_id WHERE c.username=?');
    $query->execute([$data['username']]);
    $account = $query->fetch(PDO::FETCH_ASSOC);
    check(password_verify($data['password'], $account['password_hash']), 'Password was not properly hashed');
    check($account['role_type'] === 'listener', 'Registration allowed role escalation');

    // 3. Login: wrong password refused, missing CSRF token refused, correct
    // login redirects to the listener dashboard with a NEW session id.
    $loginForm = $browser->request($path('login.php'));
    if (!is_file(dirname(__DIR__) . '/includes/oauth_config.php')) {
        check(!str_contains($loginForm['body'], 'Continue with Google'), 'Unconfigured Google sign-in was shown');
        redirected($browser->request($path('oauth/google_start.php')), $path('login.php'));
        check(str_contains($browser->request($path('login.php'))['body'], 'Google sign-in is not configured yet.'),
            'Direct Google sign-in did not explain missing configuration');
        redirected($browser->request($path('oauth/google_callback.php')), $path('login.php'));
    }
    $login = ['username' => $data['username'], 'password' => 'wrong', 'csrf_token' => token($loginForm)];
    $wrong = $browser->request($path('login.php'), $login);
    check(str_contains($wrong['body'], 'Invalid username or password.'), 'Incorrect password was accepted');
    $login['password'] = $data['password'];
    $missingToken = $login;
    unset($missingToken['csrf_token']);
    check(str_contains($browser->request($path('login.php'), $missingToken)['body'], 'form has expired'), 'Missing login CSRF was accepted');
    $oldSession = $browser->cookies['PHPSESSID'];
    redirected($browser->request($path('login.php'), $login), $path('listener/index.php'));
    check($oldSession !== $browser->cookies['PHPSESSID'], 'Login did not regenerate session ID');
    $dashboard = $browser->request($path('listener/index.php'));
    check($dashboard['status'] === 200 && str_contains($dashboard['body'], '&lt;script&gt;'), 'Listener dashboard or HTML escaping failed');
    redirected($browser->request($path('welcome.php')), $path('listener/index.php'));
    redirected($browser->request($path('login.php')), $path('listener/index.php'));

    // 4. A listener cannot see the admin dashboard (403, and no user table leaks).
    $denied = $browser->request($path('admin/index.php'));
    check($denied['status'] === 403 && !str_contains($denied['body'], 'Account directory'), 'Listener could view administrator data');
    $profile = $browser->request($path('profile.php'));
    check(str_contains($profile['body'], $data['email']) && str_contains($profile['body'], '06515')
        && str_contains($profile['body'], '203-555-0100'), 'Profile details were not preserved');

    // 5. Logout: opening the URL (GET) must not log out; a POST without the token is refused.
    redirected($browser->request($path('logout.php')), $path('welcome.php'));
    check($browser->request($path('profile.php'))['status'] === 200, 'GET logged user out');
    check($browser->request($path('logout.php'), [])['status'] === 403, 'Logout accepted missing token');

    // 6. Roles are re-read from the database on every request.
    // Promote the same account, then prove existing sessions use current database roles.
    $db->exec("UPDATE users u JOIN credentials c ON c.user_id=u.user_id JOIN roles r ON r.role_type='admin' SET u.role_id=r.role_id WHERE c.username='listener$suffix'");
    redirected($browser->request($path('welcome.php')), $path('admin/index.php'));
    $admin = $browser->request($path('admin/index.php'));
    check($admin['status'] === 200 && str_contains($admin['body'], $data['email']) && str_contains($admin['body'], '&lt;script&gt;'), 'Admin directory or escaping failed');
    // Admin forms reject requests before inserting rows or saving uploads.
    $artistForm = $browser->request($path('admin/add_artist.php'));
    check($artistForm['status'] === 200, 'Add artist form failed');
    $artistToken = token($artistForm);
    $artistCount = (int) $db->query('SELECT COUNT(*) FROM artists')->fetchColumn();
    $invalidArtist = $browser->request($path('admin/add_artist.php'), ['artist_name' => 'CSRF test artist']);
    check(str_contains($invalidArtist['body'], 'form has expired'), 'Add artist accepted missing CSRF token');
    check((int) $db->query('SELECT COUNT(*) FROM artists')->fetchColumn() === $artistCount, 'Invalid artist form inserted a row');
    $savedArtist = $browser->request($path('admin/add_artist.php'),
        ['artist_name' => 'CSRF test artist', 'csrf_token' => $artistToken]);
    check($savedArtist['status'] === 302 && $savedArtist['location'] === 'add_artist.php?added=CSRF+test+artist',
        'Valid add artist form failed');

    foreach (['album' => 'album_name', 'song' => 'song_title'] as $kind => $field) {
        $page = $path("admin/add_$kind.php");
        $form = $browser->request($page);
        check($form['status'] === 200, "Add $kind form failed");
        token($form);
        $count = (int) $db->query("SELECT COUNT(*) FROM {$kind}s")->fetchColumn();
        $invalid = $browser->request($page, [$field => "CSRF test $kind", 'artist_id' => '1']);
        check(str_contains($invalid['body'], 'form has expired'), "Add $kind accepted missing CSRF token");
        check((int) $db->query("SELECT COUNT(*) FROM {$kind}s")->fetchColumn() === $count,
            "Invalid $kind form inserted a row");
    }
    check($browser->request($path('listener/index.php'))['status'] === 403, 'Admin accessed listener-only page');
    redirected($browser->request($path('logout.php'), ['csrf_token' => token($admin)]), $path('login.php'));
    redirected($browser->request($path('profile.php')), $path('login.php'));
    $login['csrf_token'] = token($browser->request($path('login.php')));
    redirected($browser->request($path('login.php'), $login), $path('admin/index.php'));

    // Demote again: admin access must disappear immediately, without logging out.
    $db->exec("UPDATE users u JOIN credentials c ON c.user_id=u.user_id JOIN roles r ON r.role_type='listener' SET u.role_id=r.role_id WHERE c.username='listener$suffix'");
    check($browser->request($path('admin/index.php'))['status'] === 403, 'Role demotion did not revoke admin access');

    // A second account must never see the first account's profile information.
    $other = new Browser($base);
    $data['username'] = "other$suffix";
    $data['email'] = "other$suffix@example.test";
    $data['first_name'] = 'Other';
    $data['csrf_token'] = token($other->request($path('register.php')));
    redirected($other->request($path('register.php'), $data), $path('login.php?registered=1'));
    $login['username'] = $data['username'];
    $login['csrf_token'] = token($other->request($path('login.php')));
    redirected($other->request($path('login.php'), $login), $path('listener/index.php'));
    $profile = $other->request($path('profile.php'));
    check(str_contains($profile['body'], $data['email']) && !str_contains($profile['body'], "listener$suffix@example.test"), 'Profile exposed another account');

    // Delete the second account while it is logged in: its session must stop working.
    $db->exec("DELETE ct FROM contacts ct LEFT JOIN users u ON u.contact_id=ct.contact_id WHERE u.user_id IS NULL");
    $db->exec("DELETE u FROM users u JOIN credentials c ON c.user_id=u.user_id WHERE c.username='other$suffix'");
    redirected($other->request($path('profile.php')), $path('login.php'));
    echo 'PASS: ' . ($prefix ?: '/') . " registration, login, dashboards, profiles, roles, CSRF, logout, deleted accounts\n";
}

$root = dirname(__DIR__);

// ---------- Test setup: runs when the file is executed ----------
$dbName = 'decibel_test_' . bin2hex(random_bytes(6));
$server = null;
$db = null;
$log = tempnam(sys_get_temp_dir(), 'decibel-test-');
$exitCode = 0;
try {
    $host = getenv('MUSIC_DB_HOST') ?: '127.0.0.1';
    $port = getenv('MUSIC_DB_PORT') ?: '3306';

    // Connect without choosing a database, then create the throw-away one.
    $db = new PDO("mysql:host=$host;port=$port;charset=utf8mb4",
        getenv('MUSIC_DB_USER') !== false ? getenv('MUSIC_DB_USER') : 'root',
        getenv('MUSIC_DB_PASSWORD') !== false ? getenv('MUSIC_DB_PASSWORD') : '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $db->exec("CREATE DATABASE `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $db->exec("USE `$dbName`");

    // Load sql/schema.sql but skip its first lines (DROP/CREATE of the real
    // database) so only the tables and starter rows are created in the test database.
    // putenv() then tells includes/db.php to use the test database.
    $schema = file_get_contents($root . '/sql/schema.sql');
    $schema = substr($schema, strpos($schema, 'USE music_player;') + strlen('USE music_player;'));
    $db->exec($schema);
    putenv("MUSIC_DB_NAME=$dbName");

    // Start PHP's built-in web server on a free port, run the scenario against
    // it, stop it. Done once per install location.
    foreach (['root', 'subfolder'] as $mount) {
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
        check($socket !== false, $error);
        $address = stream_socket_get_name($socket, false);
        fclose($socket);
        $documentRoot = $mount === 'root' ? $root : dirname($root);
        $server = proc_open([PHP_BINARY, '-d', 'display_errors=1', '-d', 'error_reporting=-1', '-S', $address, '-t', $documentRoot],
            [0 => ['pipe', 'r'], 1 => ['file', $log, 'a'], 2 => ['file', $log, 'a']], $pipes);
        check(is_resource($server), 'Could not start PHP test server');
        fclose($pipes[0]);
        $ready = false;
        for ($attempt = 0; $attempt < 50; $attempt++) {
            $probe = @stream_socket_client('tcp://' . $address, $errno, $error, .1);
            if ($probe) {
                fclose($probe);
                $ready = true;
                break;
            }
            usleep(100000);
        }
        check($ready, 'PHP server did not start: ' . file_get_contents($log));
        exercise('http://' . $address, $mount === 'root' ? '' : '/' . basename($root), $db, $mount);
        proc_terminate($server);
        proc_close($server);
        $server = null;
    }
} catch (Throwable $error) {
    fwrite(STDERR, 'FAIL: ' . $error->getMessage() . "\n");
    $exitCode = 1;
} finally {
    if (is_resource($server)) {
        proc_terminate($server);
        proc_close($server);
    }

    // Always clean up the throw-away database, even when a check failed.
    if ($db) {
        $db->exec("DROP DATABASE IF EXISTS `$dbName`");
    }
    unlink($log);
}
exit($exitCode);
