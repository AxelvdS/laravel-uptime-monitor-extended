<?php

namespace AxelvdS\UptimeMonitorExtended\Checks;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class PingCheck
{
    protected int $timeout;
    protected int $count;
    protected float $interval;

    public function __construct(int $timeout = 3, int $count = 1, float $interval = 0.2)
    {
        $this->timeout = $timeout;
        $this->count = $count;
        $this->interval = $interval;
    }

    /**
     * Check if an IP address is reachable via ping.
     *
     * @param string $ipAddress
     * @return array ['success' => bool, 'response_time_ms' => float|null, 'error' => string|null]
     */
    public function check(string $ipAddress): array
    {
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        if ($isWindows) {
            $command = ['ping', '-n', (string) $this->count, '-w', (string) ($this->timeout * 1000), $ipAddress];
        } else {
            $command = ['ping', '-c', (string) $this->count, '-W', (string) $this->timeout, '-i', (string) $this->interval, $ipAddress];
        }

        try {
            $process = new Process($command);
            $process->setTimeout($this->timeout + 2);
            $process->run();

            if (!$process->isSuccessful()) {
                return [
                    'success' => false,
                    'response_time_ms' => null,
                    'error' => $process->getErrorOutput() ?: 'Ping failed',
                ];
            }

            $output = $process->getOutput();
            $responseTime = $this->extractResponseTime($output, $isWindows);

            return [
                'success' => true,
                'response_time_ms' => $responseTime,
                'error' => null,
            ];
        } catch (\Exception $e) {
            Log::error('Ping check failed', [
                'ip' => $ipAddress,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'response_time_ms' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Extract response time from ping output.
     */
    protected function extractResponseTime(string $output, bool $isWindows): ?float
    {
        if ($isWindows) {
            // Windows format: "time=123ms" or "time<1ms"
            if (preg_match('/time[<=](\d+(?:\.\d+)?)ms/i', $output, $matches)) {
                return (float) $matches[1];
            }
        } else {
            // Unix format: "time=123.456 ms" or "time=123 ms"
            if (preg_match('/time=(\d+(?:\.\d+)?)\s*ms/i', $output, $matches)) {
                return (float) $matches[1];
            }
            // Alternative format: "min/avg/max/mdev = 1.234/2.345/3.456/0.123 ms"
            if (preg_match('/min\/avg\/max\/mdev\s*=\s*[\d.]+\/(\d+(?:\.\d+)?)\/[\d.]+\/[\d.]+\s*ms/i', $output, $matches)) {
                return (float) $matches[1];
            }
        }

        return null;
    }
}

