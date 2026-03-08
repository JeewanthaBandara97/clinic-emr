<?php

/**
 * Header Include
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config/config.php';
}

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/csrf.php';

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? APP_NAME; ?></title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo APP_URL; ?>/assets/images/favicon.ico">
    <link rel="shortcut icon" href="<?php echo APP_URL; ?>/assets/images/favicon.ico">


    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-4.0.0.min.js"></script>
    <link href="<?php echo APP_URL; ?>/assets/css/style.css" rel="stylesheet">
        <link href="<?php echo APP_URL; ?>/assets/css/custom.css" rel="stylesheet">
</head>

<body>
    <div class="wrapper">