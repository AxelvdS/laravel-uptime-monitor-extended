<?php

namespace AxelvdS\UptimeMonitorExtended\Commands;

use AxelvdS\UptimeMonitorExtended\Models\MonitorLog;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CleanupLogs extends Command
{
    protected $signature = 'uptime-monitor:cleanup-logs';

    protected $description = 'Clean up old monitor logs based on retention period';

    public function handle(): int
    {
        $retentionDays = config('uptime-monitor-extended.log_retention_days');

        if ($retentionDays === null) {
            $this->info('Log retention is set to unlimited. No cleanup needed.');
            return 0;
        }

        $cutoffDate = Carbon::now()->subDays($retentionDays);

        $deleted = MonitorLog::where('checked_at', '<', $cutoffDate)->delete();

        $this->info("Deleted {$deleted} log entries older than {$retentionDays} days.");

        return 0;
    }
}

