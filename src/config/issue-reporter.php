<?php

return [
    /**
     * The application key from Codprez Cloud tracker.
     */
    'app_key' => env('CODPREZ_APP_KEY'),

    /**
     * The ingestion endpoint.
     */
    'endpoint' => env('CODPREZ_ENDPOINT', 'https://cloud.codprez.com/api/events'),

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
