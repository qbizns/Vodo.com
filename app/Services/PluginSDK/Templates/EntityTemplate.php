<?php

declare(strict_types=1);

namespace App\Services\PluginSDK\Templates;

use Illuminate\Support\Str;

/**
 * Entity Plugin Template
 *
 * Plugin with entity management, CRUD operations, and views.
 * Similar to Odoo/Salesforce object patterns.
 */
class EntityTemplate extends PluginTemplate
{
    protected string $entityName;
    protected string $entitySlug;

    public function __construct(string $name, array $options = [])
    {
        parent::__construct($name, $options);

        // Default entity name is singular of plugin name
        $this->entityName = $options['entity_name'] ?? Str::singular($this->name);
        $this->entitySlug = Str::kebab($this->entityName);

        // Add entity to manifest
        $this->manifest->addEntity([
            'name' => "{$this->slug}.{$this->entitySlug}",
            'label' => $this->entityName,
            'table' => "{$this->slug}_{$this->entitySlug}s",
            'type' => 'standard',
        ]);
    }

    public function getType(): string
    {
        return 'entity';
    }

    public function getDescription(): string
    {
        return 'Plugin with entity management, CRUD views, and migrations - ideal for data-driven modules.';
    }

    public function getDefaultScopes(): array
    {
        return [
            'entities:read',
            'entities:write',
            'hooks:subscribe',
        ];
    }

    public function getDirectoryStructure(): array
    {
        return [
            'config',
            'database/migrations',
            'database/seeders',
            'routes',
            'src/Entities',
            'src/Http/Controllers',
            'src/Http/Requests',
            'src/Repositories',
            'src/Services',
            'tests/Feature',
            'tests/Unit',
            'Resources/views',
            'Resources/lang/en',
        ];
    }

    public function getFiles(): array
    {
        $timestamp = date('Y_m_d_His');

        return [
            "src/{$this->name}Plugin.php" => $this->generatePluginClass(),
            "src/{$this->name}ServiceProvider.php" => $this->generateServiceProvider(),
            "src/Entities/{$this->entityName}Entity.php" => $this->generateEntityDefinition(),
            "src/Http/Controllers/{$this->entityName}Controller.php" => $this->generateController(),
            "src/Http/Requests/Store{$this->entityName}Request.php" => $this->generateStoreRequest(),
            "src/Http/Requests/Update{$this->entityName}Request.php" => $this->generateUpdateRequest(),
            "src/Repositories/{$this->entityName}Repository.php" => $this->generateRepository(),
            "database/migrations/{$timestamp}_create_{$this->slug}_{$this->entitySlug}s_table.php" => $this->generateMigration(),
            "database/seeders/{$this->entityName}Seeder.php" => $this->generateSeeder(),
            "config/{$this->slug}.php" => $this->generateConfig(),
            'routes/web.php' => $this->generateWebRoutes(),
            'routes/api.php' => $this->generateApiRoutes(),
            "tests/Feature/{$this->entityName}Test.php" => $this->generateFeatureTest(),
            "tests/Unit/{$this->entityName}EntityTest.php" => $this->generateUnitTest(),
            'composer.json' => $this->generateComposerJson(),
            'plugin.json' => $this->manifest->toJson(),
            'README.md' => $this->generateReadme(),
            '.gitignore' => $this->generateGitignore(),
            'Resources/lang/en/messages.php' => $this->generateLangFile(),
        ];
    }

    protected function generatePluginClass(): string
    {
        $description = $this->options['description'] ?? "The {$this->name} plugin.";
        $author = $this->options['author'] ?? 'Developer';
        $version = $this->options['version'] ?? '1.0.0';

        return <<<PHP
<?php

declare(strict_types=1);

namespace Plugins\\{$this->name};

use App\Services\Plugins\BasePlugin;
use App\Services\Entity\EntityRegistry;
use App\Services\View\ViewRegistry;
use Plugins\\{$this->name}\\Entities\\{$this->entityName}Entity;

/**
 * {$this->name} Plugin
 *
 * {$description}
 */
class {$this->name}Plugin extends BasePlugin
{
    protected string \$identifier = '{$this->slug}';
    protected string \$name = '{$this->name}';
    protected string \$version = '{$version}';
    protected string \$description = '{$description}';
    protected string \$author = '{$author}';

    public function boot(): void
    {
        \$this->registerEntities();
        \$this->registerViews();
        \$this->registerHooks();
        \$this->registerMenuItems();
    }

    public function install(): void
    {
        \$this->runMigrations();
        \$this->seedData();
    }

    public function uninstall(): void
    {
        // Cleanup entity data if needed
    }

    protected function registerEntities(): void
    {
        \$entityRegistry = app(EntityRegistry::class);

        // Register {$this->entityName} entity
        \$entityRegistry->register('{$this->slug}.{$this->entitySlug}', {$this->entityName}Entity::definition());
    }

    protected function registerViews(): void
    {
        \$viewRegistry = app(ViewRegistry::class);

        // List view
        \$viewRegistry->registerView('{$this->slug}.{$this->entitySlug}', 'list', [
            'title' => '{$this->entityName} List',
            'columns' => ['id', 'name', 'created_at'],
            'actions' => ['create', 'edit', 'delete'],
            'filters' => ['name'],
        ]);

        // Form view
        \$viewRegistry->registerView('{$this->slug}.{$this->entitySlug}', 'form', [
            'title' => '{$this->entityName}',
            'sections' => [
                'general' => [
                    'label' => 'General',
                    'fields' => ['name', 'description'],
                ],
            ],
        ]);
    }

    protected function registerHooks(): void
    {
        \$this->addAction('entity.{$this->slug}.{$this->entitySlug}.creating', function (\$data) {
            // Before entity creation
            return \$data;
        });

        \$this->addAction('entity.{$this->slug}.{$this->entitySlug}.created', function (\$entity) {
            // After entity creation
        });
    }

    protected function registerMenuItems(): void
    {
        \$this->registerMenu([
            [
                'id' => '{$this->slug}',
                'label' => '{$this->name}',
                'icon' => 'database',
                'sequence' => 50,
                'children' => [
                    [
                        'id' => '{$this->slug}.{$this->entitySlug}s',
                        'label' => '{$this->entityName}s',
                        'route' => '{$this->slug}.{$this->entitySlug}s.index',
                        'icon' => 'list',
                    ],
                ],
            ],
        ]);
    }

    protected function runMigrations(): void
    {
        \$migrator = app('migrator');
        \$migrator->run([__DIR__ . '/database/migrations']);
    }

    protected function seedData(): void
    {
        // Optionally seed default data
    }
}
PHP;
    }

    protected function generateEntityDefinition(): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Plugins\\{$this->name}\\Entities;

/**
 * {$this->entityName} Entity Definition
 *
 * Defines the schema, fields, and behaviors for {$this->entityName} records.
 */
class {$this->entityName}Entity
{
    /**
     * Get the entity definition.
     */
    public static function definition(): array
    {
        return [
            'label' => '{$this->entityName}',
            'label_plural' => '{$this->entityName}s',
            'table' => '{$this->slug}_{$this->entitySlug}s',
            'primary_key' => 'id',

            'fields' => [
                'id' => [
                    'type' => 'integer',
                    'label' => 'ID',
                    'auto_increment' => true,
                    'readonly' => true,
                ],
                'name' => [
                    'type' => 'string',
                    'label' => 'Name',
                    'required' => true,
                    'searchable' => true,
                    'validation' => 'required|string|max:255',
                ],
                'description' => [
                    'type' => 'text',
                    'label' => 'Description',
                    'required' => false,
                ],
                'is_active' => [
                    'type' => 'boolean',
                    'label' => 'Active',
                    'default' => true,
                ],
                'created_at' => [
                    'type' => 'datetime',
                    'label' => 'Created',
                    'readonly' => true,
                ],
                'updated_at' => [
                    'type' => 'datetime',
                    'label' => 'Updated',
                    'readonly' => true,
                ],
            ],

            'indexes' => [
                'name_index' => ['name'],
            ],

            'relationships' => [
                // Define relationships here
            ],

            'scopes' => [
                'active' => fn(\$query) => \$query->where('is_active', true),
            ],

            'behaviors' => [
                'timestamps' => true,
                'soft_deletes' => false,
            ],
        ];
    }
}
PHP;
    }

    protected function generateController(): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Plugins\\{$this->name}\\Http\\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Entity\EntityManager;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Plugins\\{$this->name}\\Http\\Requests\\Store{$this->entityName}Request;
use Plugins\\{$this->name}\\Http\\Requests\\Update{$this->entityName}Request;

/**
 * {$this->entityName} Controller
 */
class {$this->entityName}Controller extends Controller
{
    protected EntityManager \$entityManager;
    protected string \$entityType = '{$this->slug}.{$this->entitySlug}';

    public function __construct(EntityManager \$entityManager)
    {
        \$this->entityManager = \$entityManager;
    }

    /**
     * Display a listing of {$this->entitySlug}s.
     */
    public function index(Request \$request): JsonResponse
    {
        \$query = \$this->entityManager->query(\$this->entityType);

        if (\$request->has('search')) {
            \$query->where('name', 'like', '%' . \$request->input('search') . '%');
        }

        \$items = \$query->paginate(\$request->input('per_page', 15));

        return response()->json(\$items);
    }

    /**
     * Store a new {$this->entitySlug}.
     */
    public function store(Store{$this->entityName}Request \$request): JsonResponse
    {
        \$entity = \$this->entityManager->create(\$this->entityType, \$request->validated());

        return response()->json([
            'message' => '{$this->entityName} created successfully',
            'data' => \$entity,
        ], 201);
    }

    /**
     * Display the specified {$this->entitySlug}.
     */
    public function show(int \$id): JsonResponse
    {
        \$entity = \$this->entityManager->find(\$this->entityType, \$id);

        if (!\$entity) {
            return response()->json(['error' => '{$this->entityName} not found'], 404);
        }

        return response()->json(['data' => \$entity]);
    }

    /**
     * Update the specified {$this->entitySlug}.
     */
    public function update(Update{$this->entityName}Request \$request, int \$id): JsonResponse
    {
        \$entity = \$this->entityManager->update(\$this->entityType, \$id, \$request->validated());

        return response()->json([
            'message' => '{$this->entityName} updated successfully',
            'data' => \$entity,
        ]);
    }

    /**
     * Remove the specified {$this->entitySlug}.
     */
    public function destroy(int \$id): JsonResponse
    {
        \$this->entityManager->delete(\$this->entityType, \$id);

        return response()->json(['message' => '{$this->entityName} deleted successfully']);
    }
}
PHP;
    }

    protected function generateStoreRequest(): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Plugins\\{$this->name}\\Http\\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store {$this->entityName} Request
 */
class Store{$this->entityName}Request extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Add authorization logic
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The {$this->entitySlug} name is required.',
        ];
    }
}
PHP;
    }

    protected function generateUpdateRequest(): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Plugins\\{$this->name}\\Http\\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update {$this->entityName} Request
 */
class Update{$this->entityName}Request extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Add authorization logic
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
PHP;
    }

    protected function generateRepository(): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Plugins\\{$this->name}\\Repositories;

use App\Repositories\BaseRepository;

/**
 * {$this->entityName} Repository
 */
class {$this->entityName}Repository extends BaseRepository
{
    protected string \$entityType = '{$this->slug}.{$this->entitySlug}';

    /**
     * Find active {$this->entitySlug}s.
     */
    public function findActive(): array
    {
        return \$this->query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Search by name.
     */
    public function searchByName(string \$term): array
    {
        return \$this->query()
            ->where('name', 'like', "%{\$term}%")
            ->limit(20)
            ->get();
    }
}
PHP;
    }

    protected function generateMigration(): string
    {
        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{$this->slug}_{$this->entitySlug}s', function (Blueprint \$table) {
            \$table->id();
            \$table->unsignedBigInteger('tenant_id')->index();
            \$table->string('name');
            \$table->text('description')->nullable();
            \$table->boolean('is_active')->default(true);
            \$table->timestamps();

            \$table->index(['tenant_id', 'is_active']);
            \$table->index(['tenant_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$this->slug}_{$this->entitySlug}s');
    }
};
PHP;
    }

    protected function generateSeeder(): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Plugins\\{$this->name}\\Database\\Seeders;

use Illuminate\Database\Seeder;
use App\Services\Entity\EntityManager;

/**
 * {$this->entityName} Seeder
 */
class {$this->entityName}Seeder extends Seeder
{
    public function run(EntityManager \$entityManager): void
    {
        \$samples = [
            ['name' => 'Sample {$this->entityName} 1', 'description' => 'First sample record'],
            ['name' => 'Sample {$this->entityName} 2', 'description' => 'Second sample record'],
        ];

        foreach (\$samples as \$data) {
            \$entityManager->create('{$this->slug}.{$this->entitySlug}', \$data);
        }
    }
}
PHP;
    }

    protected function generateFeatureTest(): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Plugins\\{$this->name}\\Tests\\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * {$this->entityName} Feature Tests
 */
class {$this->entityName}Test extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_{$this->entitySlug}s(): void
    {
        \$response = \$this->getJson('/api/{$this->slug}/{$this->entitySlug}s');

        \$response->assertStatus(200);
    }

    public function test_can_create_{$this->entitySlug}(): void
    {
        \$data = [
            'name' => 'Test {$this->entityName}',
            'description' => 'Test description',
        ];

        \$response = \$this->postJson('/api/{$this->slug}/{$this->entitySlug}s', \$data);

        \$response->assertStatus(201)
            ->assertJsonPath('data.name', 'Test {$this->entityName}');
    }

    public function test_can_update_{$this->entitySlug}(): void
    {
        // Create a {$this->entitySlug} first, then update it
        \$this->markTestIncomplete('Implement after entity creation');
    }

    public function test_can_delete_{$this->entitySlug}(): void
    {
        // Create a {$this->entitySlug} first, then delete it
        \$this->markTestIncomplete('Implement after entity creation');
    }

    public function test_validates_required_fields(): void
    {
        \$response = \$this->postJson('/api/{$this->slug}/{$this->entitySlug}s', []);

        \$response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
PHP;
    }

    protected function generateUnitTest(): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Plugins\\{$this->name}\\Tests\\Unit;

use Tests\TestCase;
use Plugins\\{$this->name}\\Entities\\{$this->entityName}Entity;

/**
 * {$this->entityName} Entity Unit Tests
 */
class {$this->entityName}EntityTest extends TestCase
{
    public function test_entity_has_required_fields(): void
    {
        \$definition = {$this->entityName}Entity::definition();

        \$this->assertArrayHasKey('fields', \$definition);
        \$this->assertArrayHasKey('name', \$definition['fields']);
        \$this->assertArrayHasKey('id', \$definition['fields']);
    }

    public function test_name_field_is_required(): void
    {
        \$definition = {$this->entityName}Entity::definition();

        \$this->assertTrue(\$definition['fields']['name']['required']);
    }

    public function test_entity_has_timestamps(): void
    {
        \$definition = {$this->entityName}Entity::definition();

        \$this->assertTrue(\$definition['behaviors']['timestamps']);
    }
}
PHP;
    }

    protected function generateWebRoutes(): string
    {
        return <<<PHP
<?php

use Illuminate\Support\Facades\Route;
use Plugins\\{$this->name}\\Http\\Controllers\\{$this->entityName}Controller;

/*
|--------------------------------------------------------------------------
| {$this->name} Plugin Web Routes
|--------------------------------------------------------------------------
*/

Route::prefix('{$this->slug}')
    ->name('{$this->slug}.')
    ->middleware(['web', 'auth'])
    ->group(function () {
        Route::resource('{$this->entitySlug}s', {$this->entityName}Controller::class);
    });
PHP;
    }

    protected function generateApiRoutes(): string
    {
        return <<<PHP
<?php

use Illuminate\Support\Facades\Route;
use Plugins\\{$this->name}\\Http\\Controllers\\{$this->entityName}Controller;

/*
|--------------------------------------------------------------------------
| {$this->name} Plugin API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('api/{$this->slug}')
    ->name('api.{$this->slug}.')
    ->middleware(['api', 'auth:sanctum'])
    ->group(function () {
        Route::apiResource('{$this->entitySlug}s', {$this->entityName}Controller::class);
    });
PHP;
    }

    protected function generateLangFile(): string
    {
        return <<<PHP
<?php

return [
    'plugin_name' => '{$this->name}',
    '{$this->entitySlug}' => [
        'name' => '{$this->entityName}',
        'created' => '{$this->entityName} created successfully',
        'updated' => '{$this->entityName} updated successfully',
        'deleted' => '{$this->entityName} deleted successfully',
    ],
];
PHP;
    }
}
