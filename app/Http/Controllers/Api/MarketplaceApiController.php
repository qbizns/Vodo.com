<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InstalledPlugin;
use App\Models\MarketplacePlugin;
use App\Models\PluginLicense;
use App\Services\Marketplace\MarketplaceClient;
use App\Services\Marketplace\PluginManager;
use App\Services\Marketplace\LicenseManager;
use App\Services\Marketplace\UpdateManager;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MarketplaceApiController extends Controller
{
    public function __construct(
        protected MarketplaceClient $client,
        protected PluginManager $pluginManager,
        protected LicenseManager $licenseManager,
        protected UpdateManager $updateManager
    ) {}

    // =========================================================================
    // Marketplace Browsing
    // =========================================================================

    public function browse(Request $request): JsonResponse
    {
        $query = MarketplacePlugin::query();

        if ($search = $request->input('search')) {
            $query->search($search);
        }
        if ($category = $request->input('category')) {
            $query->inCategory($category);
        }
        if ($tag = $request->input('tag')) {
            $query->withTag($tag);
        }
        if ($request->boolean('free')) {
            $query->free();
        }
        if ($request->boolean('premium')) {
            $query->premium();
        }
        if ($request->boolean('featured')) {
            $query->featured();
        }

        $sort = $request->input('sort', 'popular');
        $query = match ($sort) {
            'popular' => $query->popular(),
            'rating' => $query->topRated(),
            'recent' => $query->recent(),
            'name' => $query->orderBy('name'),
            default => $query->popular(),
        };

        $plugins = $query->paginate($request->input('per_page', 20));

        return response()->json(['success' => true, 'data' => $plugins]);
    }

    public function featured(): JsonResponse
    {
        $plugins = MarketplacePlugin::featured()->limit(10)->get();
        return response()->json(['success' => true, 'data' => $plugins]);
    }

    public function categories(): JsonResponse
    {
        try {
            $categories = $this->client->getCategories();
            return response()->json(['success' => true, 'data' => $categories]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function showMarketplacePlugin(string $id): JsonResponse
    {
        $plugin = MarketplacePlugin::findByMarketplaceId($id);

        if (!$plugin) {
            try {
                $data = $this->client->getPlugin($id);
                if ($data) {
                    $plugin = $this->client->syncPlugin($data);
                }
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'error' => 'Plugin not found'], 404);
            }
        }

        if (!$plugin) {
            return response()->json(['success' => false, 'error' => 'Plugin not found'], 404);
        }

        $data = $plugin->toArray();
        $data['is_installed'] = $plugin->isInstalled();

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function syncMarketplace(): JsonResponse
    {
        try {
            $count = $this->client->syncPlugins();
            return response()->json(['success' => true, 'synced' => $count]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // Installed Plugins
    // =========================================================================

    public function installed(Request $request): JsonResponse
    {
        $query = InstalledPlugin::with(['license', 'pendingUpdate']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->boolean('has_update')) {
            $query->hasUpdate();
        }

        $plugins = $query->get();

        return response()->json(['success' => true, 'data' => $plugins]);
    }

    public function showInstalled(string $slug): JsonResponse
    {
        $plugin = InstalledPlugin::with(['license', 'pendingUpdate', 'updateHistory' => fn($q) => $q->latest()->limit(5)])
            ->where('slug', $slug)
            ->first();

        if (!$plugin) {
            return response()->json(['success' => false, 'error' => 'Plugin not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $plugin]);
    }

    public function install(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'marketplace_id' => ['required_without:package', 'string'],
            'package' => ['required_without:marketplace_id', 'file', 'mimes:zip'],
            'license_key' => ['nullable', 'string'],
        ]);

        if (isset($validated['marketplace_id'])) {
            $result = $this->pluginManager->installFromMarketplace(
                $validated['marketplace_id'],
                $validated['license_key'] ?? null
            );
        } else {
            $packagePath = $request->file('package')->store('plugin-uploads');
            $result = $this->pluginManager->installFromPackage(storage_path('app/' . $packagePath));
        }

        $status = $result['success'] ? 201 : 400;
        return response()->json($result, $status);
    }

    public function activate(string $slug): JsonResponse
    {
        $plugin = InstalledPlugin::findBySlug($slug);
        if (!$plugin) {
            return response()->json(['success' => false, 'error' => 'Plugin not found'], 404);
        }

        $result = $this->pluginManager->activate($plugin);
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    public function deactivate(string $slug): JsonResponse
    {
        $plugin = InstalledPlugin::findBySlug($slug);
        if (!$plugin) {
            return response()->json(['success' => false, 'error' => 'Plugin not found'], 404);
        }

        $result = $this->pluginManager->deactivate($plugin);
        return response()->json($result);
    }

    public function uninstall(Request $request, string $slug): JsonResponse
    {
        $plugin = InstalledPlugin::findBySlug($slug);
        if (!$plugin) {
            return response()->json(['success' => false, 'error' => 'Plugin not found'], 404);
        }

        $result = $this->pluginManager->uninstall($plugin, $request->boolean('delete_data'));
        return response()->json($result);
    }

    // =========================================================================
    // Licenses
    // =========================================================================

    public function licenses(): JsonResponse
    {
        $licenses = PluginLicense::with('plugin:id,slug,name')->get()->map(function ($license) {
            $data = $license->toArray();
            $data['masked_key'] = $license->getMaskedKey();
            $data['days_until_expiry'] = $license->getDaysUntilExpiry();
            return $data;
        });

        return response()->json(['success' => true, 'data' => $licenses]);
    }

    public function activateLicense(Request $request, string $slug): JsonResponse
    {
        $validated = $request->validate([
            'license_key' => ['required', 'string'],
            'email' => ['required', 'email'],
        ]);

        $plugin = InstalledPlugin::findBySlug($slug);
        if (!$plugin) {
            return response()->json(['success' => false, 'error' => 'Plugin not found'], 404);
        }

        $result = $this->licenseManager->activate($plugin, $validated['license_key'], $validated['email']);
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    public function deactivateLicense(string $slug): JsonResponse
    {
        $plugin = InstalledPlugin::findBySlug($slug);
        if (!$plugin) {
            return response()->json(['success' => false, 'error' => 'Plugin not found'], 404);
        }

        $result = $this->licenseManager->deactivate($plugin);
        return response()->json($result);
    }

    public function verifyLicense(string $slug): JsonResponse
    {
        $plugin = InstalledPlugin::findBySlug($slug);
        if (!$plugin) {
            return response()->json(['success' => false, 'error' => 'Plugin not found'], 404);
        }

        $result = $this->licenseManager->verify($plugin);
        return response()->json(['success' => true, 'data' => $result]);
    }

    public function licenseStatus(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->licenseManager->getStatusSummary(),
        ]);
    }

    // =========================================================================
    // Updates
    // =========================================================================

    public function checkUpdates(): JsonResponse
    {
        $updates = $this->updateManager->checkAll();
        return response()->json(['success' => true, 'data' => $updates]);
    }

    public function pendingUpdates(): JsonResponse
    {
        $updates = $this->updateManager->getPendingUpdates();
        return response()->json(['success' => true, 'data' => $updates]);
    }

    public function update(string $slug): JsonResponse
    {
        $plugin = InstalledPlugin::findBySlug($slug);
        if (!$plugin) {
            return response()->json(['success' => false, 'error' => 'Plugin not found'], 404);
        }

        $result = $this->updateManager->install($plugin);
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    public function updateAll(): JsonResponse
    {
        $results = $this->updateManager->updateAll();
        return response()->json(['success' => true, 'data' => $results]);
    }

    public function updateHistory(Request $request): JsonResponse
    {
        $history = $this->updateManager->getUpdateHistory($request->input('limit', 20));
        return response()->json(['success' => true, 'data' => $history]);
    }

    // =========================================================================
    // Statistics
    // =========================================================================

    public function stats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'plugins' => [
                    'total' => InstalledPlugin::count(),
                    'active' => InstalledPlugin::active()->count(),
                    'inactive' => InstalledPlugin::inactive()->count(),
                    'premium' => InstalledPlugin::premium()->count(),
                    'with_updates' => InstalledPlugin::hasUpdate()->count(),
                ],
                'licenses' => $this->licenseManager->getStatusSummary(),
                'updates' => $this->updateManager->getUpdateSummary(),
            ],
        ]);
    }
}
