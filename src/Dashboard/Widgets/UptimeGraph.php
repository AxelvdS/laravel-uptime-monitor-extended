<?php

namespace AxelvdS\UptimeMonitorExtended\Dashboard\Widgets;

use AxelvdS\UptimeMonitorExtended\Models\MonitorLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UptimeGraph
{
    public function getData(int $hours = 24, int $intervalMinutes = 60): array
    {
        $startDate = Carbon::now()->subHours($hours);
        $endDate = Carbon::now();

        // Get the latest log entry for each monitor in each time slot
        // This ensures we count unique monitors, not log entries
        $latestLogs = MonitorLog::whereBetween('checked_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE_FORMAT(checked_at, "%Y-%m-%d %H:00:00") as time_slot'),
                'monitor_id',
                'status'
            )
            ->whereIn('id', function ($query) use ($startDate, $endDate) {
                // Get the latest log ID for each monitor in each hour
                $query->select(DB::raw('MAX(id)'))
                    ->from('monitors_logs')
                    ->whereBetween('checked_at', [$startDate, $endDate])
                    ->groupBy(
                        DB::raw('DATE_FORMAT(checked_at, "%Y-%m-%d %H:00:00")'),
                        'monitor_id'
                    );
            })
            ->get();

        // Initialize data structure
        $data = [];
        $current = $startDate->copy()->startOfHour();

        while ($current <= $endDate) {
            $timeSlot = $current->format('Y-m-d H:00:00');
            $data[$timeSlot] = [
                'time' => $current->toIso8601String(),
                'up' => 0,
                'down' => 0,
                'ssl_issue' => 0,
            ];
            $current->addHours(1);
        }

        // Count unique monitors by status for each time slot
        foreach ($latestLogs as $log) {
            $timeSlot = $log->time_slot;
            if (isset($data[$timeSlot]) && isset($data[$timeSlot][$log->status])) {
                $data[$timeSlot][$log->status]++;
            }
        }

        return array_values($data);
    }
}

