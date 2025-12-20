<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Plugin;
use App\Services\Plugins\PluginManager;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Plugin Installation Wizard Controller (Screen 4).
 * 
 * Handles multi-step plugin installation:
 * 1. Upload/Select
 * 2. Dependencies Check
 * 3. Permissions Review
 * 4. Installation Progress
 * 5. Complete
 */
class PluginInstallController extends Controller
{
    public function __construct(
        protected PluginManager $pluginManager
    ) {}

    /**
     * Show the installation wizard.
     */
    public function create(Request $request)
    {
        $slug = $request->get('slug');
        $fromMarketplace = $request->boolean('marketplace');
        $pluginInfo = null;

        if ($slug && $fromMarketplace) {
            // Try to find in mock data first
            $mockPath = base_path('database/marketplace_plugins.json');
            if (file_exists($mockPath)) {
                $json = json_decode(file_get_contents($mockPath), true);
                $pluginInfo = $json[$slug] ?? $this->fetchMarketplacePluginInfo($slug);
            } else {
                $pluginInfo = $this->fetchMarketplacePluginInfo($slug);
            }
        }

        $data = [
            'pluginInfo' => $pluginInfo,
            'slug' => $slug,
            'fromMarketplace' => $fromMarketplace,
            'currentPage' => 'system/plugins/install',
            'currentPageLabel' => __t('plugins.install_plugin'),
            'currentPageIcon' => 'download',
        ];

        return view('backend.plugins.install.wizard', $data);
    }

    /**
     * Step 1: Check system requirements.
     */
    public function checkRequirements(Request $request): JsonResponse
    {
        $slug = $request->input('slug');
        $uploadedFile = $request->file('plugin');

        try {
            $requirements = [];
            $manifest = null;

            if ($uploadedFile) {
                // Extract and validate uploaded plugin
                $manifest = $this->extractManifest($uploadedFile);
                $slug = $manifest['slug'] ?? null;

                // Store uploaded file temporarily
                $tempPath = $uploadedFile->store('temp/plugins', 'local');
                Cache::put("plugin_install.{$slug}.temp_path", $tempPath, now()->addHour());
            } elseif ($slug) {
                // Fetch from marketplace OR mock
                $mockPath = base_path('database/marketplace_plugins.json');
                if (file_exists($mockPath)) {
                    $json = json_decode(file_get_contents($mockPath), true);
                    if (isset($json[$slug])) {
                        // Create manifest from mock data
                        $p = $json[$slug];
                        $manifest = array_merge($p['manifest'] ?? [], [
                            'slug' => $p['slug'],
                            'name' => $p['name'],
                            'version' => $p['version'],
                            'description' => $p['description'],
                            'author' => $p['author'],
                            'requires' => $p['min_system_version'] ?? '1.0.0',
                            'requires_php' => $p['min_php_version'] ?? '8.1',
                            'dependencies' => collect($p['dependencies'] ?? [])->mapWithKeys(fn($d) => [$d['name'] => $d['required_version']])->toArray(),
                            'permissions' => $p['permissions'] ?? [],
                        ]);
                    }
                }
                
                if (!$manifest) {
                    $manifest = $this->fetchMarketplaceManifest($slug);
                }
            }

            if (!$manifest) {
                throw new \Exception('Could not read plugin manifest');
            }

            // Store manifest for later steps
            Cache::put("plugin_install.{$slug}.manifest", $manifest, now()->addHour());

            // Check PHP version
            $requirements['php'] = [
                'name' => 'PHP Version',
                'required' => $manifest['requires_php'] ?? '8.1',
                'current' => PHP_VERSION,
                'status' => version_compare(PHP_VERSION, $manifest['requires_php'] ?? '8.1', '>=') ? 'ok' : 'error',
            ];

            // Check system version
            $systemVersion = config('app.version', '1.0.0');
            $requirements['system'] = [
                'name' => 'System Version',
                'required' => $manifest['requires'] ?? '1.0.0',
                'current' => $systemVersion,
                'status' => version_compare($systemVersion, $manifest['requires'] ?? '1.0.0', '>=') ? 'ok' : 'error',
            ];

            // Check disk space (50MB minimum)
            $freeSpace = disk_free_space(storage_path());
            $requiredSpace = 50 * 1024 * 1024;
            $requirements['disk'] = [
                'name' => 'Disk Space',
                'required' => '50MB',
                'current' => $this->formatBytes($freeSpace),
                'status' => $freeSpace >= $requiredSpace ? 'ok' : 'error',
            ];

            // Check write permissions
            $pluginsDir = app_path('Plugins');
            $requirements['permissions'] = [
                'name' => 'Write Permissions',
                'required' => 'Writable',
                'current' => is_writable($pluginsDir) ? 'Writable' : 'Not Writable',
                'status' => is_writable($pluginsDir) ? 'ok' : 'error',
            ];

            // Check if already installed
            $existingPlugin = Plugin::where('slug', $slug)->first();
            if ($existingPlugin) {
                $requirements['existing'] = [
                    'name' => 'Plugin Status',
                    'required' => 'Not Installed',
                    'current' => 'Already Installed (v' . $existingPlugin->version . ')',
                    'status' => 'warning',
                    'message' => 'This will update the existing plugin',
                ];
            }

            $canProceed = collect($requirements)->every(fn($r) => $r['status'] !== 'error');

            return response()->json([
                'success' => true,
                'slug' => $slug,
                'manifest' => $manifest,
                'requirements' => $requirements,
                'can_proceed' => $canProceed,
            ]);
        } catch (\Exception $e) {
            Log::error('Requirements check failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Step 2: Check and resolve dependencies.
     */
    public function checkDependencies(Request $request): JsonResponse
    {
        $slug = $request->input('slug');

        try {
            $manifest = Cache::get("plugin_install.{$slug}.manifest");
            
            if (!$manifest) {
                throw new \Exception('Installation session expired. Please start over.');
            }

            $dependencies = [];
            $toInstall = [];

            foreach ($manifest['dependencies'] ?? [] as $depSlug => $versionConstraint) {
                $installed = Plugin::where('slug', $depSlug)->first();

                $dep = [
                    'slug' => $depSlug,
                    'required_version' => $versionConstraint,
                    'installed_version' => $installed?->version,
                    'status' => 'missing',
                ];

                if ($installed) {
                    if ($installed->status === 'active') {
                        // Check version constraint
                        if ($this->checkVersionConstraint($installed->version, $versionConstraint)) {
                            $dep['status'] = 'satisfied';
                        } else {
                            $dep['status'] = 'version_mismatch';
                            $toInstall[] = $depSlug;
                        }
                    } else {
                        $dep['status'] = 'inactive';
                    }
                } else {
                    $dep['status'] = 'missing';
                    $toInstall[] = $depSlug;
                }

                $dependencies[] = $dep;
            }

            // Check if dependencies are available in marketplace
            $availableToInstall = [];
            foreach ($toInstall as $depSlug) {
                $marketplaceInfo = $this->fetchMarketplacePluginInfo($depSlug);
                if ($marketplaceInfo) {
                    $availableToInstall[] = [
                        'slug' => $depSlug,
                        'name' => $marketplaceInfo['name'],
                        'version' => $marketplaceInfo['version'],
                    ];
                }
            }

            $canProceed = collect($dependencies)->every(function ($dep) {
                return $dep['status'] === 'satisfied' || $dep['status'] === 'inactive';
            }) || !empty($availableToInstall);

            return response()->json([
                'success' => true,
                'dependencies' => $dependencies,
                'to_install' => $availableToInstall,
                'can_proceed' => $canProceed,
            ]);
        } catch (\Exception $e) {
            Log::error('Dependency check failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Step 3: Get permissions for review.
     */
    public function getPermissions(Request $request): JsonResponse
    {
        $slug = $request->input('slug');

        try {
            $manifest = Cache::get("plugin_install.{$slug}.manifest");
            
            if (!$manifest) {
                throw new \Exception('Installation session expired. Please start over.');
            }

            $permissions = $manifest['permissions'] ?? [];
            $accessRequests = [];

            // Database access
            if (!empty($manifest['migrations'])) {
                $accessRequests[] = [
                    'type' => 'database',
                    'description' => 'Create/modify database tables',
                    'details' => count($manifest['migrations']) . ' migration(s)',
                ];
            }

            // File system access
            if (!empty($manifest['assets']) || !empty($manifest['views'])) {
                $accessRequests[] = [
                    'type' => 'filesystem',
                    'description' => 'Read/write files',
                    'details' => 'Assets and view files',
                ];
            }

            // External services
            if (!empty($manifest['external_services'])) {
                $accessRequests[] = [
                    'type' => 'external',
                    'description' => 'External service access',
                    'details' => implode(', ', $manifest['external_services']),
                ];
            }

            // Email
            if (!empty($manifest['features']) && in_array('email', $manifest['features'])) {
                $accessRequests[] = [
                    'type' => 'email',
                    'description' => 'Send emails',
                    'details' => 'On behalf of users',
                ];
            }

            return response()->json([
                'success' => true,
                'permissions' => $permissions,
                'access_requests' => $accessRequests,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Step 4: Perform installation.
     */
    public function install(Request $request): JsonResponse
    {
        $slug = $request->input('slug');
        $installDependencies = $request->boolean('install_dependencies', true);

        try {
            $manifest = Cache::get("plugin_install.{$slug}.manifest");
            $tempPath = Cache::get("plugin_install.{$slug}.temp_path");

            if (!$manifest) {
                throw new \Exception('Installation session expired. Please start over.');
            }

            // Install dependencies first if requested
            if ($installDependencies && !empty($manifest['dependencies'])) {
                foreach ($manifest['dependencies'] as $depSlug => $version) {
                    $existing = Plugin::where('slug', $depSlug)->first();
                    if (!$existing || $existing->status !== 'active') {
                        // Install from marketplace
                        $this->pluginManager->installFromMarketplace($depSlug);
                    }
                }
            }

                // Clean up cache
            Cache::forget("plugin_install.{$slug}.manifest");
            Cache::forget("plugin_install.{$slug}.temp_path");

            return response()->json([
                'success' => true,
                'plugin' => [
                    'slug' => $slug,
                    'name' => $manifest['name'],
                    'version' => $manifest['version'],
                ],
                'message' => __t('plugins.installed_success', ['name' => $manifest['name']]),
            ]);
            
            /* 
            // REAL INSTALLATION LOGIC (Disabled for Mock Demo)
            // Install the main plugin
            if ($tempPath && Storage::disk('local')->exists($tempPath)) {
                $filePath = Storage::disk('local')->path($tempPath);
                $plugin = $this->pluginManager->install($filePath);
                
                // Clean up temp file
                Storage::disk('local')->delete($tempPath);
            } else {
                // Install from marketplace
                $plugin = $this->pluginManager->installFromMarketplace($slug);
            }

            // Clean up cache
            Cache::forget("plugin_install.{$slug}.manifest");
            Cache::forget("plugin_install.{$slug}.temp_path");

            return response()->json([
                'success' => true,
                'plugin' => [
                    'slug' => $plugin->slug,
                    'name' => $plugin->name,
                    'version' => $plugin->version,
                ],
                'message' => __t('plugins.installed_success', ['name' => $plugin->name]),
            ]);
            */
        } catch (\Exception $e) {
            Log::error('Plugin installation failed', [
                'slug' => $slug,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Step 5: Activate plugin after installation.
     */
    public function activate(Request $request): JsonResponse
    {
        $slug = $request->input('slug');

        try {
            $plugin = $this->pluginManager->activate($slug);

            return response()->json([
                'success' => true,
                'message' => __t('plugins.activated_success', ['name' => $plugin->name]),
                'redirect' => route('admin.plugins.show', $slug),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Server-Sent Events endpoint for installation progress.
     */
    public function progress(Request $request, string $slug): StreamedResponse
    {
        return response()->stream(function () use ($slug) {
            $progressKey = "plugin_install.{$slug}.progress";
            $lastProgress = 0;

            while (true) {
                $progress = Cache::get($progressKey, ['step' => 'waiting', 'percent' => 0]);

                if ($progress['percent'] !== $lastProgress) {
                    echo "data: " . json_encode($progress) . "\n\n";
                    ob_flush();
                    flush();
                    $lastProgress = $progress['percent'];
                }

                if ($progress['step'] === 'complete' || $progress['step'] === 'error') {
                    break;
                }

                usleep(500000); // 0.5 seconds
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    // ==================== Private Helper Methods ====================

    /**
     * Extract manifest from uploaded ZIP file.
     */
    protected function extractManifest($file): ?array
    {
        $zip = new \ZipArchive();
        $tempPath = $file->getRealPath();

        if ($zip->open($tempPath) !== true) {
            throw new \Exception('Could not open ZIP file');
        }

        // Look for plugin.json in root or first-level directory
        $manifestContent = null;
        
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            if (preg_match('/^([^\/]+\/)?plugin\.json$/', $filename)) {
                $manifestContent = $zip->getFromIndex($i);
                break;
            }
        }

        $zip->close();

        if (!$manifestContent) {
            throw new \Exception('Plugin manifest (plugin.json) not found');
        }

        $manifest = json_decode($manifestContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid plugin manifest: ' . json_last_error_msg());
        }

        return $manifest;
    }

    /**
     * Fetch plugin info from marketplace.
     */
    protected function fetchMarketplacePluginInfo(string $slug): ?array
    {
        try {
            $response = Http::timeout(10)->get(config('marketplace.api_url') . '/plugins/' . $slug);
            
            if ($response->successful()) {
                return $response->json('data');
            }
        } catch (\Exception $e) {
            Log::warning('Could not fetch marketplace plugin info', ['slug' => $slug, 'error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Fetch manifest from marketplace.
     */
    protected function fetchMarketplaceManifest(string $slug): ?array
    {
        $pluginInfo = $this->fetchMarketplacePluginInfo($slug);
        
        if ($pluginInfo) {
            return [
                'slug' => $pluginInfo['slug'],
                'name' => $pluginInfo['name'],
                'version' => $pluginInfo['version'],
                'description' => $pluginInfo['description'] ?? '',
                'author' => $pluginInfo['author']['name'] ?? '',
                'requires' => $pluginInfo['requires'] ?? '1.0.0',
                'requires_php' => $pluginInfo['requires_php'] ?? '8.1',
                'dependencies' => $pluginInfo['dependencies'] ?? [],
                'permissions' => $pluginInfo['permissions'] ?? [],
            ];
        }

        return null;
    }

    /**
     * Check if version satisfies constraint.
     */
    protected function checkVersionConstraint(string $version, string $constraint): bool
    {
        if (str_starts_with($constraint, '^')) {
            $base = substr($constraint, 1);
            $parts = explode('.', $base);
            $major = (int) ($parts[0] ?? 0);
            $nextMajor = ($major + 1) . '.0.0';

            return version_compare($version, $base, '>=') && version_compare($version, $nextMajor, '<');
        }

        if (str_starts_with($constraint, '>=')) {
            return version_compare($version, substr($constraint, 2), '>=');
        }

        return version_compare($version, $constraint, '>=');
    }

    /**
     * Format bytes to human readable.
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }
}
