<?php

namespace App\Modules\Owner\Controllers;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * The settings service instance.
     */
    protected SettingsService $settingsService;

    /**
     * Create a new controller instance.
     */
    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    /**
     * Display the settings page.
     */
    public function index(Request $request): View
    {
        $activeSection = $request->query('section', 'general');
        $pluginsWithSettings = $this->settingsService->getPluginsWithSettings();
        
        // Get content for active section
        $content = $this->getSectionContent($activeSection);

        return view('owner::settings.index', [
            'activeSection' => $activeSection,
            'pluginsWithSettings' => $pluginsWithSettings,
            'sectionContent' => $content,
        ]);
    }

    /**
     * Get general settings content (for AJAX).
     */
    public function general(): View
    {
        $definitions = $this->settingsService->getGeneralSettingsDefinitions();
        $values = $this->settingsService->getGroup('general');

        return view('backend.settings.partials.general', [
            'definitions' => $definitions,
            'values' => $values,
            'saveUrl' => route('owner.settings.general.save'),
        ]);
    }

    /**
     * Save general settings.
     */
    public function saveGeneral(Request $request)
    {
        $definitions = $this->settingsService->getGeneralSettingsDefinitions();
        $settings = [];

        // Extract all field keys from definitions
        foreach ($definitions as $group) {
            foreach ($group['fields'] as $key => $field) {
                if ($request->has($key)) {
                    $value = $request->input($key);
                    
                    // Handle toggle fields (checkboxes)
                    if ($field['type'] === 'toggle') {
                        $value = $request->boolean($key);
                    }
                    
                    // Handle number fields
                    if ($field['type'] === 'number') {
                        $value = (int) $value;
                    }
                    
                    $settings[$key] = $value;
                } elseif ($field['type'] === 'toggle') {
                    // Unchecked checkboxes are not sent, so default to false
                    $settings[$key] = false;
                }
            }
        }

        $this->settingsService->setMany($settings, 'general');

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Settings saved successfully']);
        }

        return redirect()->route('owner.settings.index')
            ->with('success', 'Settings saved successfully');
    }

    /**
     * Get plugin settings content (for AJAX).
     */
    public function plugin(string $slug): View
    {
        $fields = $this->settingsService->getPluginSettingsFields($slug);
        $values = $this->settingsService->getPluginSettings($slug);

        return view('backend.settings.partials.plugin', [
            'slug' => $slug,
            'fields' => $fields,
            'values' => $values,
            'saveUrl' => route('owner.settings.plugin.save', $slug),
        ]);
    }

    /**
     * Save plugin settings.
     */
    public function savePlugin(Request $request, string $slug)
    {
        $fields = $this->settingsService->getPluginSettingsFields($slug);
        $settings = [];

        foreach ($fields as $groupKey => $group) {
            if (isset($group['fields'])) {
                foreach ($group['fields'] as $key => $field) {
                    if ($request->has($key)) {
                        $value = $request->input($key);
                        
                        if ($field['type'] === 'toggle') {
                            $value = $request->boolean($key);
                        }
                        
                        if ($field['type'] === 'number') {
                            $value = (int) $value;
                        }
                        
                        $settings[$key] = $value;
                    } elseif ($field['type'] === 'toggle') {
                        $settings[$key] = false;
                    }
                }
            }
        }

        $this->settingsService->savePluginSettings($slug, $settings);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Plugin settings saved successfully']);
        }

        return redirect()->route('owner.settings.index', ['section' => 'plugin:' . $slug])
            ->with('success', 'Plugin settings saved successfully');
    }

    /**
     * Get section content based on active section.
     */
    protected function getSectionContent(string $section): array
    {
        if ($section === 'general') {
            return [
                'type' => 'general',
                'definitions' => $this->settingsService->getGeneralSettingsDefinitions(),
                'values' => $this->settingsService->getGroup('general'),
                'saveUrl' => route('owner.settings.general.save'),
            ];
        }

        // Plugin section (format: plugin:slug)
        if (str_starts_with($section, 'plugin:')) {
            $slug = substr($section, 7);
            $plugin = \App\Models\Plugin::where('slug', $slug)->first();
            return [
                'type' => 'plugin',
                'slug' => $slug,
                'name' => $plugin ? $plugin->name : ucfirst(str_replace('-', ' ', $slug)),
                'fields' => $this->settingsService->getPluginSettingsFields($slug),
                'values' => $this->settingsService->getPluginSettings($slug),
                'saveUrl' => route('owner.settings.plugin.save', $slug),
            ];
        }

        // Default to general
        return [
            'type' => 'general',
            'definitions' => $this->settingsService->getGeneralSettingsDefinitions(),
            'values' => $this->settingsService->getGroup('general'),
            'saveUrl' => route('owner.settings.general.save'),
        ];
    }
}
