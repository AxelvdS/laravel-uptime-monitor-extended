<?php

namespace AxelvdS\UptimeMonitorExtended\Checks;

use Illuminate\Support\Facades\Log;

class TcpPortCheck
{
    protected int $timeout;

    public function __construct(int $timeout = 3)
    {
        $this->timeout = $timeout;
    }

    /**
     * Check if a TCP port is open on a host.
     *
     * @param string $host IP address or hostname
     * @param int $port Port number
     * @return array ['success' => bool, 'response_time_ms' => float|null, 'error' => string|null]
     */
    public function check(string $host, int $port): array
    {
        $startTime = microtime(true);
        
        try {
            $connection = @fsockopen($host, $port, $errno, $errstr, $this->timeout);
            
            $responseTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
            
            if ($connection) {
                fclose($connection);
                return [
                    'success' => true,
                    'response_time_ms' => round($responseTime, 2),
                    'error' => null,
                ];
            }
            
            return [
                'success' => false,
                'response_time_ms' => null,
                'error' => $errstr ?: "Connection refused (Error: {$errno})",
            ];
        } catch (\Exception $e) {
            Log::error('TCP port check failed', [
                'host' => $host,
                'port' => $port,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'response_time_ms' => null,
                'error' => $e->getMessage(),
            ];
        }
    }
}

