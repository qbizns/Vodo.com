<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Cache\QueryCache;
use Illuminate\Support\ServiceProvider;

/**
 * Cache Service Provider - Registers caching services.
 */
class CacheServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register QueryCache as singleton
        $this->app->singleton(QueryCache::class, function ($app) {
            return new QueryCache();
        });

        // Alias for easier access
        $this->app->alias(QueryCache::class, 'query.cache');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish cache configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/cache-extended.php' => config_path('cache-extended.php'),
            ], 'cache-config');
        }

        // Register model cache observers
        $this->registerCacheObservers();
    }

    /**
     * Register cache observers for automatic invalidation.
     */
    protected function registerCacheObservers(): void
    {
        // Models that should auto-invalidate cache on change
        $modelsToObserve = config('cache.observed_models', [
            \App\Models\EntityRecord::class,
            \App\Models\EntityDefinition::class,
            \App\Models\User::class,
            \App\Models\Role::class,
            \App\Models\Permission::class,
        ]);

        foreach ($modelsToObserve as $modelClass) {
            if (class_exists($modelClass)) {
                $modelClass::saved(function ($model) {
                    app(QueryCache::class)->invalidate($model);
                });

                $modelClass::deleted(function ($model) {
                    app(QueryCache::class)->invalidate($model);
                });
            }
        }
    }
}
