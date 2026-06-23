<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CacheStrategy
{
    // Cache TTL constants (in seconds)
    const TTL_5_MINUTES = 300;

    const TTL_15_MINUTES = 900;

    const TTL_30_MINUTES = 1800;

    const TTL_1_HOUR = 3600;

    const TTL_24_HOURS = 86400;

    const TTL_1_WEEK = 604800;

    /**
     * Cache user permissions (role + permissions).
     * TTL: 1 hour (permissions change rarely, cache invalidation handled by Spatie Permission)
     */
    public static function cacheUserPermissions(int $userId, callable $callback): mixed
    {
        return Cache::remember(
            "user.permissions.{$userId}",
            self::TTL_1_HOUR,
            $callback
        );
    }

    /**
     * Cache user data (profile, settings).
     * TTL: 30 minutes
     */
    public static function cacheUserProfile(int $userId, callable $callback): mixed
    {
        return Cache::remember(
            "user.profile.{$userId}",
            self::TTL_30_MINUTES,
            $callback
        );
    }

    /**
     * Cache contract list for user.
     * TTL: 15 minutes (contracts change frequently)
     */
    public static function cacheUserContracts(int $userId, callable $callback): mixed
    {
        return Cache::remember(
            "user.contracts.{$userId}",
            self::TTL_15_MINUTES,
            $callback
        );
    }

    /**
     * Cache ticket list.
     * TTL: 5 minutes (tickets are very dynamic)
     */
    public static function cacheUserTickets(int $userId, callable $callback): mixed
    {
        return Cache::remember(
            "user.tickets.{$userId}",
            self::TTL_5_MINUTES,
            $callback
        );
    }

    /**
     * Cache static data (product info, categories).
     * TTL: 1 week (rarely changes)
     */
    public static function cacheStaticData(string $key, callable $callback): mixed
    {
        return Cache::remember(
            "static.{$key}",
            self::TTL_1_WEEK,
            $callback
        );
    }

    /**
     * Cache search results (azienda search, visura results).
     * TTL: 1 hour
     */
    public static function cacheSearchResults(string $query, callable $callback): mixed
    {
        return Cache::remember(
            'search.'.md5($query),
            self::TTL_1_HOUR,
            $callback
        );
    }

    /**
     * Invalidate user-related caches.
     */
    public static function invalidateUserCache(int $userId): void
    {
        Cache::forget("user.profile.{$userId}");
        Cache::forget("user.contracts.{$userId}");
        Cache::forget("user.tickets.{$userId}");
    }

    /**
     * Invalidate all user caches (for logout/deletion).
     */
    public static function invalidateAllUserCache(int $userId): void
    {
        self::invalidateUserCache($userId);
        Cache::forget("user.permissions.{$userId}");
    }

    /**
     * Get cache key for debugging.
     */
    public static function get(string $key): mixed
    {
        return Cache::get($key);
    }
}
