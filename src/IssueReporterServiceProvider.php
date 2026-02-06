<?php

namespace Codprez\IssueReporter;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Codprez\IssueReporter\Exceptions\IssueHandler;
use Throwable;

class IssueReporterServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/config/issue-reporter.php', 'issue-reporter');

        $this->app->singleton('issue-reporter', function ($app) {
            return new Reporter\HttpReporter($app['config']->get('issue-reporter'));
        });
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/config/issue-reporter.php' => config_path('issue-reporter.php'),
            ], 'issue-reporter-config');
        }

        $this->registerReportingHook();
    }

    protected function registerReportingHook()
    {
        $handler = $this->app->make(ExceptionHandler::class);

        if (method_exists($handler, 'reportable')) {
            $handler->reportable(function (Throwable $e) {
                \Codprez\IssueReporter\Facades\Issue::report($e);
            });
        }
    }
}
