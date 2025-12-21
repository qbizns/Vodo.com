<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Contracts\EntityRecordRepositoryInterface;
use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\Eloquent\EntityRecordRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Repository Service Provider
 *
 * Binds repository interfaces to their implementations.
 * This enables dependency injection and easy swapping of implementations.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Repository bindings.
     *
     * @var array<class-string, class-string>
     */
    protected array $repositories = [
        EntityRecordRepositoryInterface::class => EntityRecordRepository::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        foreach ($this->repositories as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
