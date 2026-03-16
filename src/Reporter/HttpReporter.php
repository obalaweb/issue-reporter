<?php

namespace Codprez\IssueReporter\Reporter;

use GuzzleHttp\Client;
use Codprez\IssueReporter\Context\ContextResolver;
use Throwable;

class HttpReporter
{
    protected array $config;
    protected Client $client;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->client = new Client([
            'timeout' => $this->config['timeout'] ?? 2.0,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);
    }

    public function report(Throwable $exception, array $customContext = [])
    {
        $primaryFrame = $this->getPrimaryFrame($exception);
        
        $payload = [
            'site_id'        => $this->config['site_id'],
            'webhook_secret' => $this->config['webhook_secret'],
            'title'          => "[Exception] " . get_class($exception),
            'description'    => $exception->getMessage(),
            'error_message'  => $exception->getMessage(),
            'status_code'    => 500,
            'environment'    => $this->config['environment'],
            
            // Rich tracking fields
            'exception_class' => get_class($exception),
            'file'           => $exception->getFile(),
            'line'           => $exception->getLine(),
            'stack_trace'    => $exception->getTraceAsString(),
            'type'           => 'exception',
            
            'request_data'   => ContextResolver::resolveRequest(),
            'server_data'    => ContextResolver::resolveServer(),
            'user_data'      => ContextResolver::resolveUser(),
        ];

        if ($primaryFrame) {
            $payload['description'] = sprintf(
                "Error in %s:%d\n\n%s",
                $primaryFrame['file'],
                $primaryFrame['line'],
                $exception->getMessage()
            );
            $payload['file'] = $primaryFrame['file'];
            $payload['line'] = $primaryFrame['line'];
        }

        // Map priority if possible, or let the backend decide
        if (isset($customContext['priority'])) {
            $payload['priority'] = $customContext['priority'];
        }

        if (isset($customContext['affected_user'])) {
            $payload['affected_user'] = $customContext['affected_user'];
        } elseif ($userData = ContextResolver::resolveUser()) {
            $payload['affected_user'] = $userData['email'] ?? $userData['id'] ?? null;
        }

        return $this->send($this->config['endpoint'], $payload);
    }

    public function capture(string $message, array $context = [], string $level = 'info')
    {
        $payload = [
            'site_id'        => $this->config['site_id'],
            'webhook_secret' => $this->config['webhook_secret'],
            'title'          => $message,
            'description'    => $context['description'] ?? $message,
            'priority'       => $context['priority'] ?? $this->mapLevelToPriority($level),
            'environment'    => $this->config['environment'],
            'request_data'   => ContextResolver::resolveRequest(),
            'server_data'    => ContextResolver::resolveServer(),
            'user_data'      => ContextResolver::resolveUser(),
            'type'           => 'capture',
        ];

        return $this->send($this->config['endpoint'], $payload);
    }

    protected function getPrimaryFrame(Throwable $exception): ?array
    {
        $trace = $exception->getTrace();
        
        // Start with the exception's own location
        $primaryFile = $exception->getFile();
        $primaryLine = $exception->getLine();

        // If the exception happened in vendor, try to find the first non-vendor frame
        if (str_contains($primaryFile, '/vendor/')) {
            foreach ($trace as $frame) {
                if (isset($frame['file']) && !str_contains($frame['file'], '/vendor/')) {
                    $primaryFile = $frame['file'];
                    $primaryLine = $frame['line'] ?? 0;
                    break;
                }
            }
        }

        // Clean up the path to be relative to base path if possible
        $basePath = base_path();
        $cleanFile = str_replace($basePath . '/', '', $primaryFile);

        return [
            'file' => $cleanFile,
            'line' => $primaryLine,
        ];
    }

    public function reportRecovery(string $message = 'Service restored', int $responseTime = null)
    {
        $payload = [
            'site_id'        => $this->config['site_id'],
            'webhook_secret' => $this->config['webhook_secret'],
            'message'        => $message,
        ];

        if ($responseTime !== null) {
            $payload['response_time'] = $responseTime;
        }

        return $this->send($this->config['recovery_endpoint'], $payload);
    }

    protected function mapLevelToPriority(string $level): string
    {
        return match ($level) {
            'emergency', 'alert', 'critical' => 'critical',
            'error' => 'high',
            'warning' => 'medium',
            default => 'low',
        };
    }

    protected function send(string $endpoint, array $payload)
    {
        if (empty($this->config['site_id']) || empty($this->config['webhook_secret'])) {
            return null;
        }

        try {
            $response = $this->client->post($endpoint, [
                'json' => $payload,
                'synchronous' => !($this->config['async'] ?? true),
            ]);
            \Illuminate\Support\Facades\Log::info("Codprez Issue Tracker Report Success", ['status' => $response->getStatusCode()]);
            return $response;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Codprez Issue Tracker Report Failed", [
                'error'    => $e->getMessage(),
                'endpoint' => $endpoint
            ]);
            return null;
        }
    }
}
