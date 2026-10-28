<?php
session_start();
$_SESSION = [];       // clear all session data
session_destroy();    // delete the session on the server

header('Location: login.php');
exit;
?>