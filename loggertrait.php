<?php
namespace app\core;

trait loggertrait {
    protected function logactivity(string $message): void {
        $logfile = __DIR__ . '/../logs/activity.log';
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($logfile, "[$timestamp] $message\n", FILE_APPEND);
    }
}
?> 