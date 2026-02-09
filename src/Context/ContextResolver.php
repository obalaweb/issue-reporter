<?php

namespace Codprez\IssueReporter\Context;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Auth;

class ContextResolver
{
    public static function resolveRequest(): array
    {
        return [
            'url' => Request::fullUrl(),
            'method' => Request::method(),
            'ip' => Request::ip(),
            'headers' => Request::header(),
            'payload' => Request::all(),
        ];
    }

    public static function resolveServer(): array
    {
        return [
            'php' => PHP_VERSION,
            'laravel' => app()->version(),
            'os' => PHP_OS,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? null,
        ];
    }

    public static function resolveUser(): ?array
    {
        $user = Auth::user();

        if (!$user) {
            return null;
        }

        return [
            'id' => $user->getAuthIdentifier(),
            'email' => $user->email ?? null,
            'name' => $user->name ?? null,
        ];
    }
}
