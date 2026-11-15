<?php
/**
 * includes/profile_details.php - prints the logged-in user's details as a list.
 *
 * Expects $user (from require_user()). Used by profile.php.
 * The array below maps "label shown on screen" => "column name in $user".
 * To show another field, add a line to the array.
 * Empty or missing values are shown as "Not provided".
 */
?>
<dl class="row mb-0">
    <?php foreach ([
        'First name' => 'first_name', 'Last name' => 'last_name',
        'Username' => 'username', 'Email' => 'email', 'Role' => 'role_type',
        'Phone' => 'phone', 'Street address' => 'street_1',
        'Address line 2' => 'street_2', 'City' => 'city',
        'State' => 'state_code', 'ZIP code' => 'post_code',
        'Member since' => 'creation_date',
    ] as $label => $field): ?>
        <dt class="col-sm-4 text-body-secondary fw-normal"><?= escape($label) ?></dt>
        <dd class="col-sm-8 text-break"><?= escape(($user[$field] ?? '') !== '' && $user[$field] !== null ? $user[$field] : 'Not provided') ?></dd>
    <?php endforeach; ?>
</dl>
