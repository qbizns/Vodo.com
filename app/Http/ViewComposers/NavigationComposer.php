<?php

namespace App\Http\ViewComposers;

use App\Services\NavigationService;
use Illuminate\View\View;

class NavigationComposer
{
    /**
     * The navigation service instance.
     */
    protected NavigationService $navigationService;

    /**
     * Create a new NavigationComposer instance.
     */
    public function __construct(NavigationService $navigationService)
    {
        $this->navigationService = $navigationService;
    }

    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        // Determine the module prefix from the view name or request
        $modulePrefix = $this->determineModulePrefix($view);

        // Get navigation groups with plugin items applied
        $navGroups = $this->navigationService->getNavGroups($modulePrefix);

        // Only set navGroups if not already set (allow controller override)
        if (!$view->offsetExists('navGroups')) {
            $view->with('navGroups', $navGroups);
        }

        // Also provide the navigation service for advanced use cases
        $view->with('navigationService', $this->navigationService);
    }

    /**
     * Determine the module prefix from the view.
     */
    protected function determineModulePrefix(View $view): string
    {
        $viewName = $view->getName();

        // Check if it's a module view (e.g., admin::dashboard)
        if (str_contains($viewName, '::')) {
            $parts = explode('::', $viewName);
            return strtolower($parts[0]);
        }

        // Try to get from current route
        $route = request()->route();
        if ($route) {
            $routeName = $route->getName();
            if ($routeName) {
                // Extract module from route name (e.g., admin.dashboard -> admin)
                $parts = explode('.', $routeName);
                if (in_array($parts[0], ['admin', 'console', 'owner'])) {
                    return $parts[0];
                }
            }
        }

        return '';
    }
}
