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

        // Group logs by time intervals
        $logs = MonitorLog::whereBetween('checked_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE_FORMAT(checked_at, "%Y-%m-%d %H:00:00") as time_slot'),
                'status',
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('time_slot', 'status')
            ->orderBy('time_slot')
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
                'ssl_expired' => 0,
            ];
            $current->addHours(1);
        }

        // Fill in actual data
        foreach ($logs as $log) {
            $timeSlot = $log->time_slot;
            if (isset($data[$timeSlot])) {
                $data[$timeSlot][$log->status] = (int) $log->count;
            }
        }

        return array_values($data);
    }
}

