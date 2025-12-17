<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Services\Menu\MenuRegistry;
use App\Services\Menu\MenuBuilder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MenuApiController extends Controller
{
    protected MenuRegistry $registry;
    protected MenuBuilder $builder;

    public function __construct(MenuRegistry $registry, MenuBuilder $builder)
    {
        $this->registry = $registry;
        $this->builder = $builder;
    }

    // Menu CRUD

    public function index(Request $request): JsonResponse
    {
        $query = Menu::query();
        if ($request->has('location')) $query->location($request->location);
        if ($request->boolean('active_only', true)) $query->active();
        return response()->json(['success' => true, 'data' => $query->ordered()->get()]);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $menu = Menu::findBySlug($slug);
        if (!$menu) return response()->json(['success' => false, 'error' => 'Menu not found'], 404);
        
        $data = $menu->toArray();
        if ($request->boolean('with_items', true)) {
            $data['items'] = $this->builder->toArray($slug, auth()->user());
        }
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:50', 'unique:menus,slug'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:50'],
            'show_icons' => ['nullable', 'boolean'],
            'show_badges' => ['nullable', 'boolean'],
            'collapsible' => ['nullable', 'boolean'],
            'roles' => ['nullable', 'array'],
            'permissions' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $menu = Menu::create($validated);
        return response()->json(['success' => true, 'data' => $menu, 'message' => 'Menu created'], 201);
    }

    public function update(Request $request, string $slug): JsonResponse
    {
        $menu = Menu::findBySlug($slug);
        if (!$menu) return response()->json(['success' => false, 'error' => 'Menu not found'], 404);
        
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:50'],
            'show_icons' => ['nullable', 'boolean'],
            'show_badges' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $menu->update(array_filter($validated, fn($v) => $v !== null));
        $this->registry->clearCache($slug);
        return response()->json(['success' => true, 'data' => $menu->fresh()]);
    }

    public function destroy(string $slug): JsonResponse
    {
        if (!$this->registry->deleteMenu($slug)) {
            return response()->json(['success' => false, 'error' => 'Menu not found'], 404);
        }
        return response()->json(['success' => true, 'message' => 'Menu deleted']);
    }

    // Menu Items

    public function items(Request $request, string $slug): JsonResponse
    {
        $menu = Menu::findBySlug($slug);
        if (!$menu) return response()->json(['success' => false, 'error' => 'Menu not found'], 404);
        
        $format = $request->input('format', 'tree');
        $user = auth()->user();
        $items = $format === 'flat' 
            ? $this->registry->getFlattened($slug, $user)
            : $this->builder->toArray($slug, $user);
        return response()->json(['success' => true, 'data' => $items]);
    }

    public function addItem(Request $request, string $slug): JsonResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'string', 'in:route,url,action,divider,header,dropdown'],
            'route' => ['nullable', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:500'],
            'icon' => ['nullable', 'string', 'max:100'],
            'badge' => ['nullable', 'string', 'max:50'],
            'badge_type' => ['nullable', 'string'],
            'roles' => ['nullable', 'array'],
            'permissions' => ['nullable', 'array'],
            'parent' => ['nullable', 'string', 'max:100'],
            'order' => ['nullable', 'integer'],
            'active' => ['nullable', 'boolean'],
            'plugin_slug' => ['required', 'string', 'max:100'],
            'children' => ['nullable', 'array'],
        ]);

        try {
            $item = $this->registry->addItem($slug, $validated, $validated['plugin_slug']);
            return response()->json(['success' => true, 'data' => $item], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    public function updateItem(Request $request, string $menuSlug, string $itemSlug): JsonResponse
    {
        $menu = Menu::findBySlug($menuSlug);
        if (!$menu) return response()->json(['success' => false, 'error' => 'Menu not found'], 404);

        $validated = $request->validate([
            'label' => ['nullable', 'string', 'max:100'],
            'icon' => ['nullable', 'string', 'max:100'],
            'badge' => ['nullable', 'string', 'max:50'],
            'order' => ['nullable', 'integer'],
            'active' => ['nullable', 'boolean'],
            'plugin_slug' => ['required', 'string', 'max:100'],
        ]);

        try {
            $item = $this->registry->updateItem($menu->id, $itemSlug, $validated, $validated['plugin_slug']);
            return response()->json(['success' => true, 'data' => $item]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    public function removeItem(Request $request, string $menuSlug, string $itemSlug): JsonResponse
    {
        $pluginSlug = $request->input('plugin_slug');
        if (!$pluginSlug) return response()->json(['success' => false, 'error' => 'plugin_slug required'], 400);

        try {
            $this->registry->removeItem($menuSlug, $itemSlug, $pluginSlug);
            return response()->json(['success' => true, 'message' => 'Item removed']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    // Reordering

    public function reorder(Request $request, string $slug): JsonResponse
    {
        $validated = $request->validate(['order' => ['required', 'array']]);
        $this->registry->reorder($slug, $validated['order']);
        return response()->json(['success' => true, 'message' => 'Items reordered']);
    }

    public function moveItem(Request $request, string $menuSlug, string $itemSlug): JsonResponse
    {
        $validated = $request->validate(['parent' => ['nullable', 'string', 'max:100']]);
        $this->registry->moveItem($menuSlug, $itemSlug, $validated['parent'] ?? null);
        return response()->json(['success' => true, 'message' => 'Item moved']);
    }

    // Rendering

    public function render(Request $request, string $slug): JsonResponse
    {
        $style = $request->input('style', 'default');
        $user = auth()->user();
        $html = match ($style) {
            'navbar' => $this->builder->renderNavbar($slug, ['user' => $user])->toHtml(),
            'sidebar' => $this->builder->renderSidebar($slug, ['user' => $user])->toHtml(),
            'dropdown' => $this->builder->renderDropdown($slug, ['user' => $user])->toHtml(),
            default => $this->builder->render($slug, ['user' => $user])->toHtml(),
        };
        return response()->json(['success' => true, 'data' => ['html' => $html]]);
    }

    public function breadcrumb(string $slug): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->builder->getBreadcrumb($slug)]);
    }

    // Meta

    public function locations(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => Menu::getLocations()]);
    }

    public function itemTypes(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => MenuItem::getTypes()]);
    }

    public function badgeTypes(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => MenuItem::getBadgeTypes()]);
    }

    public function clearCache(Request $request): JsonResponse
    {
        $this->registry->clearCache($request->input('slug'));
        return response()->json(['success' => true, 'message' => 'Cache cleared']);
    }
}
