<?php

namespace App\Modules\Owner\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Plugins\PluginManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PluginController extends Controller
{
    public function __construct(
        protected PluginManager $pluginManager
    ) {}

    /**
     * Display a listing of all plugins.
     */
    public function index()
    {
        $plugins = $this->pluginManager->all();

        return view('owner::plugins.index', [
            'plugins' => $plugins,
        ]);
    }

    /**
     * Upload and install a new plugin.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'plugin' => [
                'required',
                'file',
                'mimes:zip',
                'max:' . config('plugins.max_upload_size', 10240),
            ],
        ]);

        try {
            $plugin = $this->pluginManager->install($request->file('plugin'));

            return redirect()
                ->route('owner.plugins.index')
                ->with('success', "Plugin '{$plugin->name}' has been installed successfully. You can now activate it.");
        } catch (\Throwable $e) {
            Log::error('Plugin installation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->route('owner.plugins.index')
                ->with('error', 'Plugin installation failed: ' . $e->getMessage());
        }
    }

    /**
     * Activate a plugin.
     */
    public function activate(string $slug)
    {
        try {
            $plugin = $this->pluginManager->activate($slug);

            return redirect()
                ->route('owner.plugins.index')
                ->with('success', "Plugin '{$plugin->name}' has been activated successfully.");
        } catch (\Throwable $e) {
            Log::error('Plugin activation failed', [
                'slug' => $slug,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('owner.plugins.index')
                ->with('error', 'Plugin activation failed: ' . $e->getMessage());
        }
    }

    /**
     * Deactivate a plugin.
     */
    public function deactivate(string $slug)
    {
        try {
            $plugin = $this->pluginManager->deactivate($slug);

            return redirect()
                ->route('owner.plugins.index')
                ->with('success', "Plugin '{$plugin->name}' has been deactivated.");
        } catch (\Throwable $e) {
            Log::error('Plugin deactivation failed', [
                'slug' => $slug,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('owner.plugins.index')
                ->with('error', 'Plugin deactivation failed: ' . $e->getMessage());
        }
    }

    /**
     * Uninstall a plugin.
     */
    public function destroy(string $slug)
    {
        try {
            $plugin = $this->pluginManager->find($slug);
            $pluginName = $plugin?->name ?? $slug;

            $this->pluginManager->uninstall($slug);

            return redirect()
                ->route('owner.plugins.index')
                ->with('success', "Plugin '{$pluginName}' has been uninstalled and removed.");
        } catch (\Throwable $e) {
            Log::error('Plugin uninstall failed', [
                'slug' => $slug,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('owner.plugins.index')
                ->with('error', 'Plugin uninstall failed: ' . $e->getMessage());
        }
    }

    /**
     * Show plugin details.
     */
    public function show(string $slug)
    {
        $plugin = $this->pluginManager->findOrFail($slug);
        $migrationStatus = $this->pluginManager->migrator()->getMigrationStatus($plugin);

        return view('owner::plugins.show', [
            'plugin' => $plugin,
            'migrationStatus' => $migrationStatus,
        ]);
    }
}
