<?php

namespace AxelvdS\UptimeMonitorExtended\Dashboard\Widgets;

use AxelvdS\UptimeMonitorExtended\Models\MonitorLog;
use Spatie\UptimeMonitor\Models\Monitor;
use Illuminate\Support\Facades\DB;

class DevicesDownTable
{
    public function getData(int $limit = 10): array
    {
        // Get latest log for each monitor
        $latestLogs = MonitorLog::select('monitor_id', 'status', 'error_message', 'checked_at')
            ->whereIn('id', function ($query) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('monitors_logs')
                    ->groupBy('monitor_id');
            })
            ->whereIn('status', ['down', 'ssl_issue'])
            ->get()
            ->keyBy('monitor_id');

        // Get monitors that are down
        $monitorIds = $latestLogs->pluck('monitor_id')->toArray();
        
        $monitors = Monitor::where('is_active', true)
            ->whereIn('id', $monitorIds)
            ->limit($limit)
            ->get();

        $data = [];

        foreach ($monitors as $monitor) {
            $latestLog = $latestLogs->get($monitor->id);
            
            $data[] = [
                'id' => $monitor->id,
                'url' => $monitor->url,
                'type' => $monitor->monitor_type ?? 'https',
                'status' => $latestLog->status ?? 'unknown',
                'error_message' => $latestLog->error_message ?? null,
                'last_checked' => $latestLog->checked_at ?? null,
                'notes' => $monitor->notes,
            ];
        }

        return $data;
    }
}

