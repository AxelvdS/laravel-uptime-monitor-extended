<?php

namespace AxelvdS\UptimeMonitorExtended\Commands;

use AxelvdS\UptimeMonitorExtended\Checks\MonitorChecker;
use Illuminate\Console\Command;
use Spatie\UptimeMonitor\Models\Monitor;
use Carbon\Carbon;

class CheckMonitorsExtended extends Command
{
    protected $signature = 'uptime-monitor:check-extended {--monitor-id= : Check a specific monitor by ID}';

    protected $description = 'Check all active monitors with extended features (ping, per-monitor frequency)';

    public function handle(MonitorChecker $checker): int
    {
        $monitorId = $this->option('monitor-id');

        if ($monitorId) {
            $monitor = Monitor::find($monitorId);
            
            if (!$monitor) {
                $this->error("Monitor with ID {$monitorId} not found.");
                return 1;
            }

            return $this->checkMonitor($monitor, $checker);
        }

        // Get all active monitors that are due for checking
        $monitors = Monitor::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('last_check_at')
                    ->orWhereRaw('TIMESTAMPDIFF(MINUTE, last_check_at, NOW()) >= COALESCE(frequency_minutes, ?)', [
                        config('uptime-monitor-extended.default_frequency_minutes', 5)
                    ]);
            })
            ->get();

        if ($monitors->isEmpty()) {
            $this->info('No monitors due for checking.');
            return 0;
        }

        $this->info("Checking {$monitors->count()} monitor(s)...");

        $checked = 0;
        $failed = 0;

        foreach ($monitors as $monitor) {
            $result = $this->checkMonitor($monitor, $checker);
            
            if ($result === 0) {
                $checked++;
            } else {
                $failed++;
            }
        }

        $this->info("Completed: {$checked} successful, {$failed} failed.");

        return $failed > 0 ? 1 : 0;
    }

    protected function checkMonitor(Monitor $monitor, MonitorChecker $checker): int
    {
        $this->line("Checking monitor #{$monitor->id}: {$monitor->url}");

        try {
            $result = $checker->check($monitor);

            // Update last check timestamp
            $monitor->update(['last_check_at' => now()]);

            if ($result['success']) {
                $this->info("  ✓ {$result['status']} - {$result['message']}");
                if (isset($result['response_time_ms'])) {
                    $this->line("  Response time: {$result['response_time_ms']}ms");
                }
                return 0;
            } else {
                $this->error("  ✗ {$result['status']} - {$result['message']}");
                return 1;
            }
        } catch (\Exception $e) {
            // Ensure we log the error even if check() fails completely
            \AxelvdS\UptimeMonitorExtended\Models\MonitorLog::create([
                'monitor_id' => $monitor->id,
                'status' => 'down',
                'error_message' => $e->getMessage(),
                'checked_at' => now(),
            ]);
            
            $this->error("  ✗ Error: {$e->getMessage()}");
            \Illuminate\Support\Facades\Log::error('Monitor check failed with exception', [
                'monitor_id' => $monitor->id,
                'url' => $monitor->url,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }
}

