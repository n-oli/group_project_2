<?php
/**
 * index.php - the front door (http://localhost/<folder>/).
 * It has no page of its own; it just forwards to welcome.php, which decides
 * where the visitor should go.
 */
require __DIR__ . '/includes/app.php';
redirect_to('welcome.php');
