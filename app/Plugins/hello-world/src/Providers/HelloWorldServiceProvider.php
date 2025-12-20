<?php

namespace HelloWorld\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use HelloWorld\Events\GreetingCreated;
use HelloWorld\Events\GreetingDeleted;
use HelloWorld\Listeners\LogGreetingActivity;
use HelloWorld\Services\GreetingService;
use HelloWorld\Models\Greeting;

class HelloWorldServiceProvider extends ServiceProvider
{
    /**
     * Register any plugin services.
     */
    public function register(): void
    {
        $this->registerServices();
        $this->registerBindings();
    }

    /**
     * Bootstrap any plugin services.
     */
    public function boot(): void
    {
        $this->registerHooks();
        $this->registerFilters();
        $this->registerEventListeners();
        $this->registerViewComposers();
        $this->registerObservers();
    }

    /**
     * Register plugin services.
     */
    protected function registerServices(): void
    {
        $this->app->singleton('hello-world', function ($app) {
            return new GreetingService();
        });
    }

    /**
     * Register plugin bindings.
     */
    protected function registerBindings(): void
    {
        $this->app->bind(GreetingService::class, function ($app) {
            return $app->make('hello-world');
        });
    }

    /**
     * Register plugin hooks.
     */
    protected function registerHooks(): void
    {
        // Hook into dashboard stats
        if (app()->bound('hooks')) {
            app('hooks')->listen('dashboard.stats', function ($stats) {
                $stats['greetings'] = [
                    'total' => Greeting::count(),
                    'today' => Greeting::whereDate('created_at', today())->count(),
                ];
                return $stats;
            });
        }
    }

    /**
     * Register plugin filters.
     */
    protected function registerFilters(): void
    {
        // Filter to add greeting count to global search
        if (app()->bound('hooks')) {
            app('hooks')->addFilter('global.search.results', function ($results, $query) {
                $greetings = Greeting::where('message', 'like', "%{$query}%")
                    ->orWhere('author', 'like', "%{$query}%")
                    ->limit(5)
                    ->get();

                foreach ($greetings as $greeting) {
                    $results[] = [
                        'type' => 'greeting',
                        'title' => $greeting->message,
                        'subtitle' => 'by ' . $greeting->author,
                        'url' => route('plugins.hello-world.greetings'),
                        'icon' => 'message-square',
                    ];
                }

                return $results;
            });
        }
    }

    /**
     * Register event listeners.
     */
    protected function registerEventListeners(): void
    {
        Event::listen(GreetingCreated::class, LogGreetingActivity::class . '@handleCreated');
        Event::listen(GreetingDeleted::class, LogGreetingActivity::class . '@handleDeleted');
    }

    /**
     * Register view composers.
     */
    protected function registerViewComposers(): void
    {
        view()->composer('hello-world::*', function ($view) {
            $view->with('greetingSettings', [
                'greeting' => config('hello-world.greeting', 'Hello, World!'),
                'show_count' => config('hello-world.show_count', true),
                'display_mode' => config('hello-world.display_mode', 'card'),
            ]);
        });
    }

    /**
     * Register model observers.
     */
    protected function registerObservers(): void
    {
        // Observers would be registered here if needed
        // Greeting::observe(GreetingObserver::class);
    }
}
