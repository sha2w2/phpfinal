<?php
namespace app\core;

trait loggerTrait {
    protected function logActivity(string $message): void {
        $logFile = __DIR__ . '/../logs/activity.log';
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
    }
}
?>