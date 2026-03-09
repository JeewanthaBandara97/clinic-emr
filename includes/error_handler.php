<?php
/**
 * Centralized Error and Exception Handling
 * Clinic EMR System
 */

// Lightweight includes (avoid heavy dependencies here)
require_once __DIR__ . '/logger.php';

// Try to get flash utilities if available
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
$hasFunctions = false;
try {
    require_once __DIR__ . '/functions.php';
    $hasFunctions = function_exists('setFlash');
} catch (Throwable $t) {
    $hasFunctions = false;
}

// Convert PHP errors into exceptions for unified handling
set_error_handler(function (int $severity, string $message, string $file, int $line) {
    if (!(error_reporting() & $severity)) {
        // Respect current error_reporting level
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Global exception handler
set_exception_handler(function ($e) use ($hasFunctions) {
    try {
        $cls = is_object($e) ? get_class($e) : 'Throwable';
        Logger::error("Uncaught $cls: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
        if (method_exists($e, 'getTraceAsString')) {
            Logger::debug($e->getTraceAsString());
        }
    } catch (Throwable $t) {
        // Swallow logging failures
    }

    // Decide output behavior based on display_errors
    $display = ini_get('display_errors');
    if (!headers_sent()) {
        http_response_code(500);
    }

    if ($display) {
        // Developer mode: show detailed error inline
        $safe = htmlspecialchars((string)$e, ENT_QUOTES, 'UTF-8');
        echo "<pre style=\"padding:20px;margin:20px;border:1px solid #e2e8f0;background:#fff7f7;color:#b91c1c;white-space:pre-wrap;\">$safe</pre>";
    } else {
        // Production: set a flash and show a friendly page
        if ($hasFunctions) {
            setFlash('danger', 'An unexpected error occurred. Please try again or contact support.');
        }
        $errorPage = __DIR__ . '/error_page.php';
        if (is_file($errorPage)) {
            include $errorPage;
        } else {
            echo 'An unexpected error occurred. Please try again later.';
        }
    }
});

// Fatal error handler
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        try {
            Logger::error("Fatal Error: {$error['message']} in {$error['file']}:{$error['line']}");
        } catch (Throwable $t) {
            // Ignore logger failures
        }
        if (!headers_sent()) {
            http_response_code(500);
        }
        if (!ini_get('display_errors')) {
            $errorPage = __DIR__ . '/error_page.php';
            if (is_file($errorPage)) {
                include $errorPage;
            } else {
                echo 'A system error occurred. Please try again later.';
            }
        }
    }
});
