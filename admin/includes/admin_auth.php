<?php
/**
 * admin/includes/admin_auth.php
 * -------------------------------------------------------
 * Include this at the top of every admin page.
 * It reuses the existing auth_check and adds role guard.
 * -------------------------------------------------------
 */
require_once __DIR__ . '/../../includes/auth_check.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Not an admin — kick back to main site
    header('Location: ../index.php');
    exit();
}