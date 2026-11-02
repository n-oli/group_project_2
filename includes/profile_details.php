<?php // $user comes from the authenticated database query. ?>
<dl class="details">
    <?php foreach ([
        'First name' => 'first_name', 'Last name' => 'last_name',
        'Username' => 'username', 'Email' => 'email', 'Role' => 'role_type',
        'Phone' => 'phone', 'Street address' => 'street_1',
        'Address line 2' => 'street_2', 'City' => 'city',
        'State' => 'state_code', 'ZIP code' => 'post_code',
        'Member since' => 'creation_date',
    ] as $label => $field): ?>
        <div><dt><?= escape($label) ?></dt><dd><?= escape(($user[$field] ?? '') !== '' && $user[$field] !== null ? $user[$field] : 'Not provided') ?></dd></div>
    <?php endforeach; ?>
</dl>
