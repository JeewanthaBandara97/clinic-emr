<?php
/**
 * Simple Logger - writes to project logs folder
 */

class Logger {
    private static $logFile = null;
    private static $logDir = null;
    
    public static function init() {
        self::$logDir = __DIR__ . '/../logs';
        
        // Create logs directory if it doesn't exist
        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0755, true);
        }
        
        // Create today's log file
        $date = date('Y-m-d');
        self::$logFile = self::$logDir . '/app-' . $date . '.log';
    }
    
    public static function log($message, $level = 'INFO') {
        if (!self::$logFile) {
            self::init();
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$level] $message\n";
        
        // Write to file
        file_put_contents(self::$logFile, $logMessage, FILE_APPEND);
    }
    
    public static function debug($message) {
        self::log($message, 'DEBUG');
    }
    
    public static function info($message) {
        self::log($message, 'INFO');
    }
    
    public static function error($message) {
        self::log($message, 'ERROR');
        // Also log to PHP error log
        error_log($message);
    }
    
    public static function warning($message) {
        self::log($message, 'WARN');
    }
    
    public static function getLogFile() {
        if (!self::$logFile) {
            self::init();
        }
        return self::$logFile;
    }
}

// Initialize logger on include
Logger::init();
?>
