<?php

namespace AxelvdS\UptimeMonitorExtended\Dashboard\Widgets;

use AxelvdS\UptimeMonitorExtended\Models\MonitorLog;
use Spatie\UptimeMonitor\Models\Monitor;
use Illuminate\Support\Facades\DB;

class UpDownWidget
{
    public function getData(): array
    {
        // Get latest status for each monitor
        $latestLogs = MonitorLog::select('monitor_id', 'status', 'checked_at')
            ->whereIn('id', function ($query) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('monitors_logs')
                    ->groupBy('monitor_id');
            })
            ->get();

        $up = 0;
        $down = 0;
        $sslExpired = 0;
        $total = Monitor::where('is_active', true)->count();

        foreach ($latestLogs as $log) {
            match ($log->status) {
                'up' => $up++,
                'down' => $down++,
                'ssl_issue' => $sslExpired++,
                default => null,
            };
        }

        return [
            'total' => $total,
            'up' => $up,
            'down' => $down,
            'ssl_issue' => $sslExpired,
            'percentage_up' => $total > 0 ? round(($up / $total) * 100, 2) : 0,
        ];
    }
}

