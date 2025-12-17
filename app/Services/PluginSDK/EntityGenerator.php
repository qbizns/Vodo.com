<?php

declare(strict_types=1);

namespace App\Services\PluginSDK;

use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;

/**
 * EntityGenerator - Generates entity scaffolding within plugins.
 * 
 * Creates:
 * - Model class
 * - Migration
 * - Controller (optional)
 * - Form request (optional)
 * - Entity registration code
 */
class EntityGenerator
{
    protected Filesystem $files;
    protected string $pluginsPath;

    public function __construct(Filesystem $files)
    {
        $this->files = $files;
        $this->pluginsPath = base_path('plugins');
    }

    /**
     * Generate an entity within a plugin.
     */
    public function generate(string $pluginName, string $entityName, array $fields, array $options = []): array
    {
        $pluginPath = $this->pluginsPath . '/' . Str::studly($pluginName);
        
        if (!$this->files->exists($pluginPath)) {
            throw new \RuntimeException("Plugin '{$pluginName}' does not exist");
        }

        $entityClass = Str::studly($entityName);
        $entitySlug = Str::snake($entityName);
        $tableName = Str::snake(Str::plural($entityName));
        $pluginSlug = Str::kebab($pluginName);

        $files = [];

        // Generate Model
        $modelPath = $pluginPath . "/Models/{$entityClass}.php";
        $files['model'] = $this->generateModel($pluginName, $entityClass, $tableName, $fields, $options);
        $this->files->put($modelPath, $files['model']);

        // Generate Migration
        $timestamp = date('Y_m_d_His');
        $migrationName = "{$timestamp}_create_{$tableName}_table.php";
        $migrationPath = $pluginPath . "/database/migrations/{$migrationName}";
        $files['migration'] = $this->generateMigration($tableName, $fields, $options);
        $this->files->put($migrationPath, $files['migration']);

        // Generate Controller if requested
        if ($options['controller'] ?? true) {
            $controllerPath = $pluginPath . "/Http/Controllers/{$entityClass}Controller.php";
            $files['controller'] = $this->generateController($pluginName, $entityClass, $entitySlug);
            $this->files->put($controllerPath, $files['controller']);
        }

        // Generate entity registration snippet
        $files['registration'] = $this->generateRegistration($pluginSlug, $entitySlug, $entityClass, $tableName, $fields);

        return [
            'entity' => $entityClass,
            'plugin' => $pluginName,
            'table' => $tableName,
            'files' => array_keys($files),
            'registration_code' => $files['registration'],
        ];
    }

    /**
     * Generate model class.
     */
    protected function generateModel(
        string $pluginName,
        string $entityClass,
        string $tableName,
        array $fields,
        array $options
    ): string {
        $fillable = array_keys($fields);
        $fillableStr = "'" . implode("', '", $fillable) . "'";
        
        $casts = $this->generateCasts($fields);
        $castsStr = empty($casts) ? '' : "\n    protected \$casts = [\n        " . 
            implode(",\n        ", array_map(fn($k, $v) => "'{$k}' => '{$v}'", array_keys($casts), $casts)) . 
            ",\n    ];";

        $traits = ['use App\Traits\HasTenant;'];
        if ($options['soft_deletes'] ?? false) {
            $traits[] = 'use Illuminate\Database\Eloquent\SoftDeletes;';
        }
        if ($options['versioning'] ?? false) {
            $traits[] = 'use App\Traits\HasVersioning;';
        }
        $traitsUse = implode("\n", array_map(fn($t) => "    {$t}", $traits));

        $useStatements = ["use Illuminate\Database\Eloquent\Model;"];
        if ($options['soft_deletes'] ?? false) {
            $useStatements[] = "use Illuminate\Database\Eloquent\SoftDeletes;";
        }

        $relations = $this->generateRelations($fields);

        return <<<PHP
<?php

declare(strict_types=1);

namespace Plugins\\{$pluginName}\\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTenant;
{$this->generateAdditionalUses($fields)}

/**
 * {$entityClass} Model
 */
class {$entityClass} extends Model
{
{$traitsUse}

    protected \$table = '{$tableName}';

    protected \$fillable = [
        {$fillableStr},
    ];{$castsStr}
{$relations}
}
PHP;
    }

    /**
     * Generate migration.
     */
    protected function generateMigration(string $tableName, array $fields, array $options): string
    {
        $columns = $this->generateMigrationColumns($fields);
        $softDeletes = ($options['soft_deletes'] ?? false) ? "\n            \$table->softDeletes();" : '';
        $versioning = ($options['versioning'] ?? false) ? "\n            \$table->unsignedInteger('version')->default(1);" : '';

        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{$tableName}', function (Blueprint \$table) {
            \$table->id();
            \$table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
{$columns}{$versioning}{$softDeletes}
            \$table->timestamps();
            
            \$table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$tableName}');
    }
};
PHP;
    }

    /**
     * Generate controller.
     */
    protected function generateController(string $pluginName, string $entityClass, string $entitySlug): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Plugins\\{$pluginName}\\Http\\Controllers;

use App\Http\Controllers\Controller;
use Plugins\\{$pluginName}\\Models\\{$entityClass};
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * {$entityClass} Controller
 */
class {$entityClass}Controller extends Controller
{
    /**
     * Display a listing.
     */
    public function index(): JsonResponse
    {
        \$items = {$entityClass}::paginate();
        return response()->json(\$items);
    }

    /**
     * Store a newly created resource.
     */
    public function store(Request \$request): JsonResponse
    {
        \$validated = \$request->validate([
            // Add validation rules
        ]);

        \$item = {$entityClass}::create(\$validated);
        return response()->json(\$item, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show({$entityClass} \${$entitySlug}): JsonResponse
    {
        return response()->json(\${$entitySlug});
    }

    /**
     * Update the specified resource.
     */
    public function update(Request \$request, {$entityClass} \${$entitySlug}): JsonResponse
    {
        \$validated = \$request->validate([
            // Add validation rules
        ]);

        \${$entitySlug}->update(\$validated);
        return response()->json(\${$entitySlug});
    }

    /**
     * Remove the specified resource.
     */
    public function destroy({$entityClass} \${$entitySlug}): JsonResponse
    {
        \${$entitySlug}->delete();
        return response()->json(null, 204);
    }
}
PHP;
    }

    /**
     * Generate entity registration code.
     */
    protected function generateRegistration(
        string $pluginSlug,
        string $entitySlug,
        string $entityClass,
        string $tableName,
        array $fields
    ): string {
        $fieldsDef = $this->generateFieldsDefinition($fields);

        return <<<PHP
// Add this to your plugin's registerEntities() method:

\$entityRegistry->register('{$pluginSlug}.{$entitySlug}', [
    'label' => '{$entityClass}',
    'label_plural' => '{$entityClass}s',
    'table' => '{$tableName}',
    'model' => \\Plugins\\{$entityClass}\\Models\\{$entityClass}::class,
    'fields' => {$fieldsDef},
    'features' => ['tenant', 'activity'],
]);
PHP;
    }

    /**
     * Generate migration columns.
     */
    protected function generateMigrationColumns(array $fields): string
    {
        $lines = [];
        
        foreach ($fields as $name => $definition) {
            $type = is_array($definition) ? ($definition['type'] ?? 'string') : $definition;
            $nullable = is_array($definition) && ($definition['nullable'] ?? false);
            $default = is_array($definition) ? ($definition['default'] ?? null) : null;
            $unique = is_array($definition) && ($definition['unique'] ?? false);

            $line = match ($type) {
                'string' => "\$table->string('{$name}')",
                'text' => "\$table->text('{$name}')",
                'integer', 'int' => "\$table->integer('{$name}')",
                'bigint' => "\$table->bigInteger('{$name}')",
                'decimal', 'money' => "\$table->decimal('{$name}', 15, 2)",
                'boolean', 'bool' => "\$table->boolean('{$name}')",
                'date' => "\$table->date('{$name}')",
                'datetime' => "\$table->dateTime('{$name}')",
                'timestamp' => "\$table->timestamp('{$name}')",
                'json' => "\$table->json('{$name}')",
                'many2one' => "\$table->foreignId('{$name}')",
                default => "\$table->string('{$name}')",
            };

            if ($nullable) {
                $line .= "->nullable()";
            }

            if ($default !== null) {
                $defaultVal = is_string($default) ? "'{$default}'" : $default;
                $line .= "->default({$defaultVal})";
            }

            if ($unique) {
                $line .= "->unique()";
            }

            $lines[] = "            {$line};";
        }

        return implode("\n", $lines);
    }

    /**
     * Generate casts array.
     */
    protected function generateCasts(array $fields): array
    {
        $casts = [];
        
        foreach ($fields as $name => $definition) {
            $type = is_array($definition) ? ($definition['type'] ?? 'string') : $definition;
            
            $cast = match ($type) {
                'integer', 'int' => 'integer',
                'boolean', 'bool' => 'boolean',
                'decimal', 'money' => 'decimal:2',
                'date' => 'date',
                'datetime', 'timestamp' => 'datetime',
                'json' => 'array',
                default => null,
            };

            if ($cast) {
                $casts[$name] = $cast;
            }
        }

        return $casts;
    }

    /**
     * Generate relations.
     */
    protected function generateRelations(array $fields): string
    {
        $relations = [];

        foreach ($fields as $name => $definition) {
            if (!is_array($definition)) {
                continue;
            }

            $type = $definition['type'] ?? 'string';
            
            if ($type === 'many2one' && isset($definition['relation'])) {
                $relatedModel = $definition['relation'];
                $relationName = Str::camel(str_replace('_id', '', $name));
                
                $relations[] = <<<PHP

    /**
     * Get the {$relationName}.
     */
    public function {$relationName}()
    {
        return \$this->belongsTo(\\{$relatedModel}::class, '{$name}');
    }
PHP;
            }
        }

        return implode("\n", $relations);
    }

    /**
     * Generate additional use statements.
     */
    protected function generateAdditionalUses(array $fields): string
    {
        $uses = [];
        
        // Check if we need relation imports
        foreach ($fields as $name => $definition) {
            if (is_array($definition) && ($definition['type'] ?? '') === 'many2one') {
                $uses[] = "use Illuminate\Database\Eloquent\Relations\BelongsTo;";
                break;
            }
        }

        return empty($uses) ? '' : implode("\n", $uses);
    }

    /**
     * Generate fields definition array as string.
     */
    protected function generateFieldsDefinition(array $fields): string
    {
        $lines = ["["];
        
        foreach ($fields as $name => $definition) {
            if (is_array($definition)) {
                $props = [];
                foreach ($definition as $key => $value) {
                    $val = is_string($value) ? "'{$value}'" : var_export($value, true);
                    $props[] = "'{$key}' => {$val}";
                }
                $lines[] = "        '{$name}' => [" . implode(', ', $props) . "],";
            } else {
                $lines[] = "        '{$name}' => ['type' => '{$definition}'],";
            }
        }
        
        $lines[] = "    ]";
        
        return implode("\n", $lines);
    }
}
