<?php

namespace Codprez\IssueReporter\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void report(\Throwable $exception, array $context = [])
 * @method static void capture(string $message, array $context = [], string $level = 'info')
 * 
 * @see \Codprez\IssueReporter\Reporter\HttpReporter
 */
class Issue extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'issue-reporter';
    }
}
