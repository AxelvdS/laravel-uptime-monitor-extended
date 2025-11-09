<?php

namespace AxelvdS\UptimeMonitorExtended\Checks;

use AxelvdS\UptimeMonitorExtended\Checks\PingCheck;
use AxelvdS\UptimeMonitorExtended\Checks\TcpPortCheck;
use AxelvdS\UptimeMonitorExtended\Models\MonitorLog;
use Spatie\UptimeMonitor\Models\Monitor;
use Illuminate\Support\Facades\Log;

class MonitorChecker
{
    protected PingCheck $pingCheck;
    protected TcpPortCheck $tcpPortCheck;

    public function __construct()
    {
        $this->pingCheck = new PingCheck(
            config('uptime-monitor-extended.ping.timeout', 3),
            config('uptime-monitor-extended.ping.count', 1),
            config('uptime-monitor-extended.ping.interval', 0.2)
        );
        $this->tcpPortCheck = new TcpPortCheck(
            config('uptime-monitor-extended.ping.timeout', 3)
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
            'tcp' => $this->checkTcpPort($monitor),
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
     * Check monitor via TCP port.
     */
    protected function checkTcpPort(Monitor $monitor): array
    {
        // Get the raw URL value, ensuring it's a string
        $url = (string) $monitor->url;
        
        $hostAndPort = $this->extractHostAndPortFromUrl($url);
        
        if (!$hostAndPort) {
            return [
                'success' => false,
                'status' => 'down',
                'message' => "Invalid host:port format (e.g., 192.168.1.1:22 or example.com:3306). Got: {$url}",
            ];
        }

        $result = $this->tcpPortCheck->check($hostAndPort['host'], $hostAndPort['port']);

        $status = $result['success'] ? 'up' : 'down';

        // Log the check
        $this->logCheck($monitor, $status, $result);

        return [
            'success' => $result['success'],
            'status' => $status,
            'response_time_ms' => $result['response_time_ms'],
            'message' => $result['error'] ?? "Port {$hostAndPort['port']} is open",
        ];
    }

    /**
     * Check monitor via HTTP/HTTPS (uses Spatie's functionality).
     */
    protected function checkHttp(Monitor $monitor): array
    {
        // Use HTTP client to check the URL directly
        // This avoids relying on Spatie's internal methods which may not exist in v4
        try {
            $url = (string) $monitor->url;
            $startTime = microtime(true);
            
            $client = new \GuzzleHttp\Client([
                'timeout' => 10,
                'verify' => true,
                'allow_redirects' => true,
                'http_errors' => false, // Don't throw exceptions on HTTP errors
            ]);
            
            $response = $client->get($url);
            $responseTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
            
            $statusCode = $response->getStatusCode();
            $isUp = $statusCode >= 200 && $statusCode < 400;
            
            $status = $isUp ? 'up' : 'down';
            
            // Check SSL certificate if HTTPS
            if (str_starts_with($url, 'https://')) {
                try {
                    $certInfo = $this->getCertificateInfo($url);
                    if ($certInfo && isset($certInfo['valid_to'])) {
                        $expiresAt = \Carbon\Carbon::createFromTimestamp($certInfo['valid_to']);
                        if ($expiresAt->isPast()) {
                            $status = 'ssl_expired';
                        } elseif ($expiresAt->diffInDays(now()) < 7) {
                            $status = 'ssl_expiring';
                        }
                    }
                    
                    // Check for revoked certificate (basic check - full OCSP/CRL check would be more complex)
                    // Note: This is a simplified check. Full revocation checking requires OCSP or CRL lookup
                    // Revoked certificates will typically fail during the initial connection
                    // (Removed isset check as it's not needed - revocation is detected via exception handling)
                } catch (\Exception $e) {
                    // SSL check failed, but HTTP might still be up
                    // If the error mentions revoked, expired, or untrusted, we'll catch it in the exception handler
                }
            }

            // Log the check
            $this->logCheck($monitor, $status, [
                'response_time_ms' => round($responseTime, 2),
                'error' => $isUp ? null : "HTTP {$statusCode}",
            ]);

            return [
                'success' => $isUp && $status !== 'ssl_expired',
                'status' => $status,
                'response_time_ms' => round($responseTime, 2),
                'message' => $isUp ? 'HTTP check successful' : "HTTP check failed (Status: {$statusCode})",
            ];
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $errorMessage = $e->getMessage();
            $status = 'down';
            
            // Check for various SSL certificate errors
            $lowerError = strtolower($errorMessage);
            
            if (str_contains($lowerError, 'certificate has expired') || 
                str_contains($lowerError, 'certificate expired') ||
                str_contains($lowerError, 'ssl certificate') && str_contains($lowerError, 'expired')) {
                $status = 'ssl_expired';
            } elseif (str_contains($lowerError, 'certificate has been revoked') ||
                      str_contains($lowerError, 'certificate revoked') ||
                      str_contains($lowerError, 'revoked certificate')) {
                $status = 'ssl_expired'; // Treat revoked as expired for now
            } elseif (str_contains($lowerError, 'self signed certificate') ||
                      str_contains($lowerError, 'self-signed')) {
                $status = 'ssl_expired'; // Treat self-signed as expired
            } elseif (str_contains($lowerError, 'untrusted') ||
                      str_contains($lowerError, 'unable to verify')) {
                $status = 'ssl_expired'; // Treat untrusted as expired
            }
            
            Log::error('HTTP check failed', [
                'monitor_id' => $monitor->id,
                'error' => $errorMessage,
                'status' => $status,
            ]);

            $this->logCheck($monitor, $status, [
                'error' => $errorMessage,
            ]);

            return [
                'success' => false,
                'status' => $status,
                'message' => $errorMessage,
            ];
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $status = 'down';
            
            // Check for SSL certificate errors in generic exceptions too
            $lowerError = strtolower($errorMessage);
            
            if (str_contains($lowerError, 'certificate has expired') || 
                str_contains($lowerError, 'certificate expired') ||
                str_contains($lowerError, 'ssl certificate') && str_contains($lowerError, 'expired')) {
                $status = 'ssl_expired';
            } elseif (str_contains($lowerError, 'certificate has been revoked') ||
                      str_contains($lowerError, 'certificate revoked') ||
                      str_contains($lowerError, 'revoked certificate')) {
                $status = 'ssl_expired'; // Treat revoked as expired for now
            }
            
            Log::error('HTTP check failed', [
                'monitor_id' => $monitor->id,
                'error' => $errorMessage,
                'status' => $status,
            ]);

            $this->logCheck($monitor, $status, [
                'error' => $errorMessage,
            ]);

            return [
                'success' => false,
                'status' => $status,
                'message' => $errorMessage,
            ];
        }
    }

    /**
     * Get SSL certificate information for HTTPS URLs.
     */
    protected function getCertificateInfo(string $url): ?array
    {
        try {
            $parsed = parse_url($url);
            $host = $parsed['host'] ?? null;
            $port = $parsed['port'] ?? 443;
            
            if (!$host) {
                return null;
            }
            
            $context = stream_context_create([
                'ssl' => [
                    'capture_peer_cert' => true,
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ]);
            
            $socket = @stream_socket_client(
                "ssl://{$host}:{$port}",
                $errno,
                $errstr,
                5,
                STREAM_CLIENT_CONNECT,
                $context
            );
            
            if (!$socket) {
                return null;
            }
            
            $params = stream_context_get_params($socket);
            $cert = $params['options']['ssl']['peer_certificate'] ?? null;
            
            if ($cert) {
                return openssl_x509_parse($cert);
            }
            
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Detect monitor type from URL.
     */
    protected function detectMonitorType(string $url): string
    {
        // Check for TCP port format (host:port)
        if ($this->extractHostAndPortFromUrl($url)) {
            return 'tcp';
        }

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
        // Convert to string if it's an object
        $url = (string) $url;
        
        // Remove protocol if present (http://, https://, or just //)
        $url = preg_replace('#^https?://#', '', $url);
        $url = preg_replace('#^//+#', '', $url); // Remove leading slashes
        
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
     * Extract host and port from URL.
     */
    protected function extractHostAndPortFromUrl(string $url): ?array
    {
        // Convert to string if it's an object
        $url = (string) $url;
        
        // Remove protocol if present (http://, https://, or just //)
        $url = preg_replace('#^https?://#', '', $url);
        $url = preg_replace('#^//+#', '', $url); // Remove leading slashes (handles // prefix from Spatie's URL object)
        
        // Remove path if present
        $url = preg_replace('#/.*$#', '', $url);

        // Check if port is specified
        if (preg_match('#^(.+):(\d+)$#', $url, $matches)) {
            $host = $matches[1];
            $port = (int) $matches[2];
            
            // Validate port range
            if ($port < 1 || $port > 65535) {
                return null;
            }
            
            return [
                'host' => $host,
                'port' => $port,
            ];
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

