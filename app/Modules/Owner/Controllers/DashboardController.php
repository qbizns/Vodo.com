<?php

namespace App\Modules\Owner\Controllers;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Services\NavigationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * The navigation service instance.
     */
    protected NavigationService $navigationService;

    /**
     * The dashboard service instance.
     */
    protected DashboardService $dashboardService;

    /**
     * Create a new controller instance.
     */
    public function __construct(NavigationService $navigationService, DashboardService $dashboardService)
    {
        $this->navigationService = $navigationService;
        $this->dashboardService = $dashboardService;
    }

    /**
     * Get user type and ID for the current authenticated user.
     */
    protected function getUserContext(): array
    {
        $user = Auth::guard('owner')->user();
        return [
            'type' => 'owner',
            'id' => $user ? $user->id : 0,
        ];
    }

    /**
     * Get the full navigation groups for the navigation board.
     */
    protected function getAllNavGroups(): array
    {
        return $this->navigationService->getNavGroupsForNavBoard('owner');
    }

    /**
     * Display the main dashboard.
     */
    public function index(): View
    {
        $userContext = $this->getUserContext();
        $widgets = $this->dashboardService->getMainDashboardWidgets($userContext['type'], $userContext['id']);
        $unusedWidgets = $this->dashboardService->getUnusedWidgets('main', $userContext['type'], $userContext['id']);

        return view('owner::dashboard', [
            'widgets' => $widgets,
            'unusedWidgets' => $unusedWidgets,
            'currentDashboard' => 'main',
        ]);
    }

    /**
     * Display a plugin-specific dashboard.
     */
    public function pluginDashboard(string $slug): View
    {
        $userContext = $this->getUserContext();
        $widgets = $this->dashboardService->getPluginDashboardWidgets($slug, $userContext['type'], $userContext['id']);
        $unusedWidgets = $this->dashboardService->getUnusedWidgets($slug, $userContext['type'], $userContext['id']);

        // Get plugin info for title
        $plugin = \App\Models\Plugin::where('slug', $slug)->first();
        $currentPlugin = null;
        
        if ($plugin) {
            $instance = $this->dashboardService->getPluginsWithDashboards();
            $currentPlugin = collect($instance)->firstWhere('slug', $slug);
        }

        return view('owner::dashboard-plugin', [
            'widgets' => $widgets,
            'unusedWidgets' => $unusedWidgets,
            'currentDashboard' => $slug,
            'currentPlugin' => $currentPlugin,
        ]);
    }

    /**
     * Get widgets for main dashboard (AJAX).
     */
    public function getWidgets(): JsonResponse
    {
        $userContext = $this->getUserContext();
        $widgets = $this->dashboardService->getMainDashboardWidgets($userContext['type'], $userContext['id']);
        
        return response()->json([
            'success' => true,
            'widgets' => $widgets,
        ]);
    }

    /**
     * Get widgets for plugin dashboard (AJAX).
     */
    public function getPluginWidgets(string $slug): JsonResponse
    {
        $userContext = $this->getUserContext();
        $widgets = $this->dashboardService->getPluginDashboardWidgets($slug, $userContext['type'], $userContext['id']);
        
        return response()->json([
            'success' => true,
            'widgets' => $widgets,
        ]);
    }

    /**
     * Save widget layout (AJAX).
     */
    public function saveLayout(Request $request): JsonResponse
    {
        $userContext = $this->getUserContext();
        $widgets = $request->input('widgets', []);
        $dashboard = $request->input('dashboard', 'main');

        $success = $this->dashboardService->saveWidgetLayout($dashboard, $widgets, $userContext['type'], $userContext['id']);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Layout saved successfully' : 'Failed to save layout',
        ]);
    }

    /**
     * Save plugin dashboard widget layout (AJAX).
     */
    public function savePluginLayout(Request $request, string $slug): JsonResponse
    {
        $userContext = $this->getUserContext();
        $widgets = $request->input('widgets', []);

        $success = $this->dashboardService->saveWidgetLayout($slug, $widgets, $userContext['type'], $userContext['id']);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Layout saved successfully' : 'Failed to save layout',
        ]);
    }

    /**
     * Add a widget to dashboard (AJAX).
     */
    public function addWidget(Request $request): JsonResponse
    {
        $userContext = $this->getUserContext();
        $widgetId = $request->input('widget_id');
        $dashboard = $request->input('dashboard', 'main');
        $pluginSlug = $request->input('plugin_slug');

        $widget = $this->dashboardService->addWidget($dashboard, $widgetId, $userContext['type'], $userContext['id'], $pluginSlug);

        if ($widget) {
            // Hydrate with definition
            $availableWidgets = $this->dashboardService->getAvailableWidgets($dashboard);
            $widget->definition = $availableWidgets[$widgetId] ?? [];

            return response()->json([
                'success' => true,
                'widget' => $widget,
                'message' => 'Widget added successfully',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to add widget',
        ], 400);
    }

    /**
     * Remove a widget from dashboard (AJAX).
     */
    public function removeWidget(Request $request, string $widgetId): JsonResponse
    {
        $userContext = $this->getUserContext();
        $dashboard = $request->input('dashboard', 'main');

        $success = $this->dashboardService->removeWidget($dashboard, $widgetId, $userContext['type'], $userContext['id']);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Widget removed successfully' : 'Failed to remove widget',
        ]);
    }

    /**
     * Get widget data (AJAX).
     */
    public function getWidgetData(Request $request, string $widgetId): JsonResponse
    {
        $pluginSlug = $request->input('plugin_slug');
        
        $data = $this->dashboardService->getWidgetData($widgetId, $pluginSlug);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Update widget settings (AJAX).
     */
    public function updateWidgetSettings(Request $request, string $widgetId): JsonResponse
    {
        $userContext = $this->getUserContext();
        $dashboard = $request->input('dashboard', 'main');
        $settings = $request->input('settings', []);

        $success = $this->dashboardService->updateWidgetSettings($dashboard, $widgetId, $settings, $userContext['type'], $userContext['id']);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Settings updated successfully' : 'Failed to update settings',
        ]);
    }

    /**
     * Reset dashboard to defaults (AJAX).
     */
    public function resetDashboard(Request $request): JsonResponse
    {
        $userContext = $this->getUserContext();
        $dashboard = $request->input('dashboard', 'main');

        $success = $this->dashboardService->resetDashboard($dashboard, $userContext['type'], $userContext['id']);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Dashboard reset successfully' : 'Failed to reset dashboard',
        ]);
    }

    /**
     * Get available widgets that can be added (AJAX).
     */
    public function getAvailableWidgets(Request $request): JsonResponse
    {
        $userContext = $this->getUserContext();
        $dashboard = $request->input('dashboard', 'main');

        $unused = $this->dashboardService->getUnusedWidgets($dashboard, $userContext['type'], $userContext['id']);

        return response()->json([
            'success' => true,
            'widgets' => $unused,
        ]);
    }

    /**
     * Display the navigation board.
     */
    public function navigationBoard(): View
    {
        return view('owner::navigation-board', [
            'allNavGroups' => $this->getAllNavGroups(),
            'visibleItems' => ['dashboard', 'sites', 'databases'],
        ]);
    }

    /**
     * Display placeholder pages.
     */
    public function placeholder(string $page): View
    {
        $pageTitle = ucwords(str_replace('-', ' ', $page));
        
        return view('owner::placeholder', [
            'pageSlug' => $page,
            'pageTitle' => $pageTitle,
        ]);
    }
}
