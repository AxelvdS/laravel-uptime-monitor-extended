<?php

namespace AxelvdS\UptimeMonitorExtended\Checks;

use AxelvdS\UptimeMonitorExtended\Checks\PingCheck;
use AxelvdS\UptimeMonitorExtended\Models\MonitorLog;
use Spatie\UptimeMonitor\Models\Monitor;
use Illuminate\Support\Facades\Log;

class MonitorChecker
{
    protected PingCheck $pingCheck;

    public function __construct()
    {
        $this->pingCheck = new PingCheck(
            config('uptime-monitor-extended.ping.timeout', 3),
            config('uptime-monitor-extended.ping.count', 1),
            config('uptime-monitor-extended.ping.interval', 0.2)
        );
    }

    /**
     * Check a monitor based on its type.
     */
    public function check(Monitor $monitor): array
    {
        if (!$monitor->is_active) {
            return [
                'success' => false,
                'status' => 'skipped',
                'message' => 'Monitor is inactive',
            ];
        }

        $monitorType = $monitor->monitor_type ?? $this->detectMonitorType($monitor->url);

        return match ($monitorType) {
            'ping' => $this->checkPing($monitor),
            'http', 'https' => $this->checkHttp($monitor),
            default => $this->checkHttp($monitor), // Default to HTTP check
        };
    }

    /**
     * Check monitor via ping.
     */
    protected function checkPing(Monitor $monitor): array
    {
        $ipAddress = $this->extractIpFromUrl($monitor->url);
        
        if (!$ipAddress) {
            return [
                'success' => false,
                'status' => 'down',
                'message' => 'Invalid IP address',
            ];
        }

        $result = $this->pingCheck->check($ipAddress);

        $status = $result['success'] ? 'up' : 'down';

        // Log the check
        $this->logCheck($monitor, $status, $result);

        return [
            'success' => $result['success'],
            'status' => $status,
            'response_time_ms' => $result['response_time_ms'],
            'message' => $result['error'] ?? 'Ping successful',
        ];
    }

    /**
     * Check monitor via HTTP/HTTPS (uses Spatie's functionality).
     */
    protected function checkHttp(Monitor $monitor): array
    {
        // Use Spatie's built-in check functionality
        // This will trigger Spatie's events and logging
        try {
            $monitor->checkUptime();
            
            // Determine status based on Spatie's check results
            $status = $monitor->uptime_check_failed_at ? 'down' : 'up';
            
            // Check SSL if applicable
            if (str_starts_with($monitor->url, 'https://')) {
                if ($monitor->certificate_expires_at && $monitor->certificate_expires_at->isPast()) {
                    $status = 'ssl_expired';
                } elseif ($monitor->certificate_expires_at && $monitor->certificate_expires_at->isFuture() && $monitor->certificate_expires_at->diffInDays(now()) < 7) {
                    $status = 'ssl_expiring';
                }
            }

            // Log the check
            $this->logCheck($monitor, $status, [
                'response_time_ms' => null, // Spatie doesn't expose this directly
                'error' => $monitor->uptime_check_failed_at ? 'Uptime check failed' : null,
            ]);

            return [
                'success' => $status === 'up',
                'status' => $status,
                'message' => $status === 'up' ? 'HTTP check successful' : 'HTTP check failed',
            ];
        } catch (\Exception $e) {
            Log::error('HTTP check failed', [
                'monitor_id' => $monitor->id,
                'error' => $e->getMessage(),
            ]);

            $this->logCheck($monitor, 'down', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status' => 'down',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Detect monitor type from URL.
     */
    protected function detectMonitorType(string $url): string
    {
        if (filter_var($url, FILTER_VALIDATE_IP)) {
            return 'ping';
        }

        if (str_starts_with($url, 'https://')) {
            return 'https';
        }

        if (str_starts_with($url, 'http://')) {
            return 'http';
        }

        // Default to https if no protocol specified
        return 'https';
    }

    /**
     * Extract IP address from URL.
     */
    protected function extractIpFromUrl(string $url): ?string
    {
        // Remove protocol if present
        $url = preg_replace('#^https?://#', '', $url);
        
        // Remove port if present
        $url = preg_replace('#:\d+$#', '', $url);
        
        // Remove path if present
        $url = preg_replace('#/.*$#', '', $url);

        // Validate IP
        if (filter_var($url, FILTER_VALIDATE_IP)) {
            return $url;
        }

        return null;
    }

    /**
     * Log the check result.
     */
    protected function logCheck(Monitor $monitor, string $status, array $result): void
    {
        MonitorLog::create([
            'monitor_id' => $monitor->id,
            'status' => $status,
            'response_time_ms' => $result['response_time_ms'] ?? null,
            'error_message' => $result['error'] ?? null,
            'metadata' => [
                'monitor_type' => $monitor->monitor_type,
                'url' => $monitor->url,
            ],
            'checked_at' => now(),
        ]);
    }
}

