<?php

namespace AxelvdS\UptimeMonitorExtended\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitorLog extends Model
{
    protected $table = 'monitors_logs';

    protected $fillable = [
        'monitor_id',
        'status',
        'response_time_ms',
        'error_message',
        'metadata',
        'checked_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'checked_at' => 'datetime',
    ];

    /**
     * Get the monitor that owns this log entry.
     */
    public function monitor(): BelongsTo
    {
        return $this->belongsTo(\Spatie\UptimeMonitor\Models\Monitor::class);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('checked_at', [$startDate, $endDate]);
    }
}

