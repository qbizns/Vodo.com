<?php

/**
 * Plugin Cache Implementation Test Script
 * 
 * Run this from the project root:
 *   php tests/plugin-cache-test.php
 * 
 * Or include it in your test suite.
 */

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Plugins\PluginCacheManager;
use App\Models\Plugin;

echo "=== Plugin Cache Implementation Tests ===\n\n";

$cacheManager = app(PluginCacheManager::class);
$passed = 0;
$failed = 0;

// Test 1: Cache manager is properly instantiated
echo "Test 1: PluginCacheManager instantiation... ";
if ($cacheManager instanceof PluginCacheManager) {
    echo "PASS\n";
    $passed++;
} else {
    echo "FAIL\n";
    $failed++;
}

// Test 2: Cache path is correct
echo "Test 2: Cache path... ";
$expectedPath = base_path('bootstrap/cache/plugins.php');
if ($cacheManager->getCachePath() === $expectedPath) {
    echo "PASS\n";
    $passed++;
} else {
    echo "FAIL (expected: {$expectedPath}, got: {$cacheManager->getCachePath()})\n";
    $failed++;
}

// Test 3: Initial cache state
echo "Test 3: Initial cache exists check... ";
$initialExists = $cacheManager->exists();
echo ($initialExists ? "EXISTS" : "NOT EXISTS") . " (OK)\n";
$passed++;

// Test 4: Rebuild cache
echo "Test 4: Rebuild cache... ";
try {
    $result = $cacheManager->rebuild();
    if ($result) {
        echo "PASS\n";
        $passed++;
    } else {
        echo "FAIL (returned false)\n";
        $failed++;
    }
} catch (\Exception $e) {
    echo "FAIL ({$e->getMessage()})\n";
    $failed++;
}

// Test 5: Cache exists after rebuild
echo "Test 5: Cache exists after rebuild... ";
if ($cacheManager->exists()) {
    echo "PASS\n";
    $passed++;
} else {
    echo "FAIL\n";
    $failed++;
}

// Test 6: Load cache data
echo "Test 6: Load cache data... ";
$data = $cacheManager->load();
if (is_array($data) && isset($data['plugins']) && isset($data['generated_at'])) {
    echo "PASS\n";
    $passed++;
} else {
    echo "FAIL (invalid structure)\n";
    $failed++;
}

// Test 7: Get active plugins
echo "Test 7: Get active plugins from cache... ";
$activePlugins = $cacheManager->getActivePlugins();
if (is_array($activePlugins)) {
    $count = count($activePlugins);
    echo "PASS ({$count} plugins)\n";
    $passed++;
} else {
    echo "FAIL\n";
    $failed++;
}

// Test 8: Verify plugin data structure
echo "Test 8: Plugin data structure... ";
$structureValid = true;
foreach ($activePlugins as $slug => $pluginData) {
    if (!isset($pluginData['main_class']) || !isset($pluginData['path'])) {
        $structureValid = false;
        break;
    }
}
if ($structureValid) {
    echo "PASS\n";
    $passed++;
} else {
    echo "FAIL (missing required fields)\n";
    $failed++;
}

// Test 9: Get metadata
echo "Test 9: Get metadata... ";
$metadata = $cacheManager->getMetadata();
if (is_array($metadata) && isset($metadata['exists']) && isset($metadata['plugin_count'])) {
    echo "PASS\n";
    $passed++;
} else {
    echo "FAIL\n";
    $failed++;
}

// Test 10: Config values
echo "Test 10: Config safe_mode exists... ";
if (config('plugins.safe_mode') !== null) {
    echo "PASS (value: " . (config('plugins.safe_mode') ? 'true' : 'false') . ")\n";
    $passed++;
} else {
    echo "FAIL\n";
    $failed++;
}

echo "Test 11: Config use_cache exists... ";
if (config('plugins.use_cache') !== null) {
    echo "PASS (value: " . (config('plugins.use_cache') ? 'true' : 'false') . ")\n";
    $passed++;
} else {
    echo "FAIL\n";
    $failed++;
}

// Test 12: PluginManager has cache manager
echo "Test 12: PluginManager has cache manager... ";
$pluginManager = app(\App\Services\Plugins\PluginManager::class);
if ($pluginManager->cache() instanceof PluginCacheManager) {
    echo "PASS\n";
    $passed++;
} else {
    echo "FAIL\n";
    $failed++;
}

// Summary
echo "\n=== Results ===\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";

if ($failed === 0) {
    echo "\n✓ All tests passed!\n";
    exit(0);
} else {
    echo "\n✗ Some tests failed.\n";
    exit(1);
}
