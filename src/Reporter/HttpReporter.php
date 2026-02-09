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
        $payload = [
            'site_id'        => $this->config['site_id'],
            'webhook_secret' => $this->config['webhook_secret'],
            'title'          => "[Exception] " . get_class($exception),
            'description'    => $exception->getMessage(),
            'error_message'  => $exception->getTraceAsString(),
            'status_code'    => 500, // Default for exceptions
            'environment'    => $this->config['environment'],
            'request'        => ContextResolver::resolveRequest(),
            'server'         => ContextResolver::resolveServer(),
            'user'           => ContextResolver::resolveUser(),
        ];

        // Map priority if possible, or let the backend decide
        if (isset($customContext['priority'])) {
            $payload['priority'] = $customContext['priority'];
        }

        if (isset($customContext['affected_user'])) {
            $payload['affected_user'] = $customContext['affected_user'];
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
            'request'        => ContextResolver::resolveRequest(),
            'server'         => ContextResolver::resolveServer(),
            'user'           => ContextResolver::resolveUser(),
        ];

        return $this->send($this->config['endpoint'], $payload);
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
