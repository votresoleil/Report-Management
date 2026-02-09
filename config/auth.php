<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Prevent multiple inclusions
if (defined('AUTH_INCLUDED')) {
    return;
}
define('AUTH_INCLUDED', true);

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

function isAdmin() {
    return $_SESSION['role'] === 'admin';
}
