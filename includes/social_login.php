<?php
// Calls Google's API and returns the JSON reply as an array.
function oauth_request(string $url, ?array $postFields = null, ?string $accessToken = null): array
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    if ($postFields !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
    }
    if ($accessToken !== null) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
    }
    $body = curl_exec($ch);
    if ($body === false) {
        throw new RuntimeException('Could not reach Google: ' . curl_error($ch));
    }
    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $data = json_decode($body, true);
    if ($status !== 200 || !is_array($data)) {
        throw new RuntimeException('Google sign-in failed. Please try again.');
    }
    return $data;
}

// Finds or creates the user for this Google account, then logs them in.
function social_login(PDO $pdo, string $provider, string $providerUserId, string $email,
                      bool $emailVerified, string $firstName, string $lastName): void
{
    // 1. Already linked?
    $stmt = $pdo->prepare('SELECT user_id FROM social_accounts WHERE provider = ? AND provider_user_id = ?');
    $stmt->execute([$provider, $providerUserId]);
    $userId = $stmt->fetchColumn();

    if (!$userId) {
        // 2. Existing account with the same email? Link it only if Google verified the email.
        $stmt = $pdo->prepare('SELECT u.user_id FROM users u JOIN contacts c ON c.contact_id = u.contact_id WHERE c.email = ?');
        $stmt->execute([$email]);
        $userId = $stmt->fetchColumn();

        if ($userId && !$emailVerified) {
            throw new RuntimeException('Your Google email is not verified.');
        }

        $pdo->beginTransaction();
        try {
            if (!$userId) {
                // 3. Brand-new person: create the contact and the user (listener role).
                $stmt = $pdo->prepare('INSERT INTO contacts (first_name, last_name, email) VALUES (?, ?, ?)');
                $stmt->execute([$firstName, $lastName, $email]);
                $contactId = $pdo->lastInsertId();

                $stmt = $pdo->prepare("INSERT INTO users (role_id, contact_id)
                                       SELECT role_id, ? FROM roles WHERE role_type = 'listener'");
                $stmt->execute([$contactId]);
                $userId = $pdo->lastInsertId();
            }
            $stmt = $pdo->prepare('INSERT INTO social_accounts (user_id, provider, provider_user_id) VALUES (?, ?, ?)');
            $stmt->execute([$userId, $provider, $providerUserId]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw new RuntimeException('Could not create your account.');
        }
    }

    // 4. Log them in (same session keys login.php uses).
    $stmt = $pdo->prepare('SELECT COALESCE(cr.username, ct.first_name) AS username, r.role_type
                           FROM users u
                           JOIN contacts ct ON ct.contact_id = u.contact_id
                           JOIN roles r ON r.role_id = u.role_id
                           LEFT JOIN credentials cr ON cr.user_id = u.user_id
                           WHERE u.user_id = ?');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();

    session_regenerate_id(true);
    $_SESSION['user_id']  = (int) $userId;
    $_SESSION['username'] = $row['username'];
    $_SESSION['role']     = $row['role_type'];
    unset($_SESSION['csrf_token']);
}