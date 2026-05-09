<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

if (! function_exists('current_user')) {
    function current_user(): ?User
    {
        return auth()->user();
    }
}

if (! function_exists('current_organization_id')) {
    function current_organization_id(): ?int
    {
        return auth()->user()?->current_organization_id;
    }
}

if (! function_exists('generate_uuid')) {
    function generate_uuid(): string
    {
        return (string) Str::uuid();
    }
}

if (! function_exists('cache_remember_organization')) {
    function cache_remember_organization(string $key, int $seconds, callable $callback): mixed
    {
        $orgId = current_organization_id();
        $cacheKey = $orgId ? "{$key}:org:{$orgId}" : $key;

        return Cache::remember($cacheKey, $seconds, $callback);
    }
}

if (! function_exists('storage_url')) {
    function storage_url(?string $path): ?string
    {
        if ($path === null) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}

if (! function_exists('file_size_for_humans')) {
    function file_size_for_humans(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }
}

if (! function_exists('is_valid_uuid')) {
    function is_valid_uuid(string $value): bool
    {
        return Str::isUuid($value);
    }
}
