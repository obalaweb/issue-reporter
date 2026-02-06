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
                'X-App-Key' => $this->config['app_key'],
                'Accept'    => 'application/json',
            ],
        ]);
    }

    public function report(Throwable $exception, array $customContext = [])
    {
        $payload = [
            'app_key'     => $this->config['app_key'],
            'environment' => $this->config['environment'],
            'type'        => 'exception',
            'level'       => 'error',
            'message'     => $exception->getMessage(),
            'exception'   => [
                'class' => get_class($exception),
                'code'  => (string) $exception->getCode(),
                'file'  => $exception->getFile(),
                'line'  => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ],
            'request'     => ContextResolver::resolveRequest(),
            'server'      => ContextResolver::resolveServer(),
            'user'        => ContextResolver::resolveUser(),
            'occurred_at' => now()->toRfc3339String(),
        ];

        // Merge custom context if any
        if (!empty($customContext)) {
            $payload['custom'] = $customContext;
        }

        return $this->send($payload);
    }

    public function capture(string $message, array $context = [], string $level = 'info')
    {
        $payload = [
            'app_key'     => $this->config['app_key'],
            'environment' => $this->config['environment'],
            'type'        => 'custom',
            'level'       => $level,
            'message'     => $message,
            'request'     => ContextResolver::resolveRequest(),
            'server'      => ContextResolver::resolveServer(),
            'user'        => ContextResolver::resolveUser(),
            'occurred_at' => now()->toRfc3339String(),
            'custom'      => $context,
        ];

        return $this->send($payload);
    }

    protected function send(array $payload)
    {
        if (empty($this->config['app_key'])) {
            return null;
        }

        try {
            return $this->client->post($this->config['endpoint'], [
                'json' => $payload,
                'synchronous' => !$this->config['async'],
            ]);
        } catch (\Exception $e) {
            // Silently fail to not break the application
            return null;
        }
    }
}
