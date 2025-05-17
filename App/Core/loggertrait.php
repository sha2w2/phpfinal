<?php
namespace App\Core;

trait LoggerTrait {
    protected function logActivity(string $message): void {
        $logDir = __DIR__ . '/../logs/';
        
        
        if (!file_exists($logDir)) {
            mkdir($logDir, 0777, true);
        }
        
        $logFile = $logDir . 'activity.log';
        $timestamp = date('Y-m-d H:i:s');
        
          if (file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND) === false) {
            error_log("Failed to write to log file: $logFile");
        }
    }
}
?>