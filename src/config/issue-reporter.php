<?php

return [
    /**
     * The Site ID from Codprez Cloud tracker.
     */
    'site_id' => env('CODPREZ_SITE_ID'),

    /**
     * The Webhook Secret for the site.
     */
    'webhook_secret' => env('CODPREZ_WEBHOOK_SECRET'),

    /**
     * The ingestion endpoint.
     */
    'endpoint' => env('CODPREZ_ENDPOINT', 'https://codprez.cloud/api/webhook/issue'),

    /**
     * The recovery endpoint.
     */
    'recovery_endpoint' => env('CODPREZ_RECOVERY_ENDPOINT', 'https://codprez.cloud/api/webhook/recovery'),

    /**
     * The environment name.
     */
    'environment' => env('APP_ENV', 'production'),

    /**
     * Whether to report errors asynchronously.
     */
    'async' => env('CODPREZ_ASYNC', true),

    /**
     * Timeout for HTTP requests in seconds.
     */
    'timeout' => 2.0,
];
