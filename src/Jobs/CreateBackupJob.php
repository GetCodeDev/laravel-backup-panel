<?php

namespace PavelMironchik\LaravelBackupPanel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Spatie\Backup\Events\BackupHasFailed;
use Spatie\Backup\Tasks\Backup\BackupJobFactory;

class CreateBackupJob implements ShouldQueue
{

    use Dispatchable, InteractsWithQueue, Queueable;

    protected $option;

    protected $db_name;

    protected $sanitized;


    public function __construct($option = '', $db_name, $sanitized = false)
    {
        $this->option    = $option;
        $this->db_name   = $db_name;
        $this->sanitized = $sanitized;
    }


    public function handle()
    {
        try {

            $backupJob = BackupJobFactory::createFromArray(config('backup'));

            if ($this->option === 'only-db') {
                $backupJob->dontBackupFilesystem();
            }

            if ($this->option === 'only-files') {
                $backupJob->dontBackupDatabases();
            }

            if ($this->db_name) {
                $backupJob->onlyDbName([$this->db_name]);
            }

            if ($this->sanitized) {
                $backupJob->setSanitized();
            }

            /*if ( ! empty($this->option)) {
                $prefix = str_replace('_', '-', $this->option).'-';

                $backupJob->setFilename($prefix.date('Y-m-d-H-i-s').'.zip');
            }*/

            $backupJob->run();

        } catch (Exception $exception) {
            consoleOutput()->error("Backup failed because: {$exception->getMessage()}.");

            if (! $disableNotifications) {
                event(new BackupHasFailed($exception));
            }

            return 1;
        }

    }
}
