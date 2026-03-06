<?php
// includes/auth_check.php
// -------------------------------------------------------
// Existing auth check – keep as-is, just make sure
// session_start() is called and user_id is verified.
// No changes needed here if it already looks like this:
// -------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    // Determine the correct redirect path
    $root = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    // Go up to project root
    header("Location: /index.php");
    exit();
}