<?php
/**
 * includes/errors.php - prints a form's validation errors as a red box.
 *
 * HOW TO USE IT
 * 1. In your page, collect problems in an array called $errors:
 * $errors[] = 'Song title is required.';
 * 2. In the HTML, include this file where the box should appear.
 * If $errors is empty, nothing is printed.
 * Every message goes through htmlspecialchars() so it is safe to display.
 */

// Shows a form's $errors list as a red Bootstrap alert. Include it where the box should appear.
if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
