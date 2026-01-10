<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use VodoCommerce\Http\Resources\EmailTemplateResource;
use VodoCommerce\Models\EmailTemplate;

class EmailTemplateController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = EmailTemplate::query();

        // Filter by store
        if ($request->has('store_id')) {
            $query->forStore((int) $request->get('store_id'));
        }

        // Filter by type
        if ($request->has('type')) {
            $query->ofType($request->get('type'));
        }

        // Filter by category
        if ($request->has('category')) {
            $query->inCategory($request->get('category'));
        }

        // Filter by trigger event
        if ($request->has('trigger_event')) {
            $query->forTrigger($request->get('trigger_event'));
        }

        // Filter active templates
        if ($request->boolean('active_only')) {
            $query->active();
        }

        // Filter default templates
        if ($request->boolean('default_only')) {
            $query->defaults();
        }

        // Search
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        // Relationships
        if ($request->has('include')) {
            $includes = explode(',', $request->get('include'));
            $query->with($includes);
        }

        return EmailTemplateResource::collection(
            $query->paginate($request->get('per_page', 15))
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'store_id' => 'required|exists:commerce_stores,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'default_subject' => 'nullable|string|max:255',
            'default_preview_text' => 'nullable|string',
            'html_content' => 'nullable|string',
            'text_content' => 'nullable|string',
            'available_variables' => 'nullable|array',
            'required_variables' => 'nullable|array',
            'thumbnail' => 'nullable|string|max:255',
            'design_config' => 'nullable|array',
            'type' => 'required|in:transactional,marketing,automated,custom',
            'trigger_event' => 'nullable|in:order_placed,order_shipped,order_delivered,cart_abandoned,customer_registered,password_reset,product_back_in_stock,price_drop,review_request,manual,other',
            'trigger_conditions' => 'nullable|array',
            'trigger_delay_minutes' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'meta' => 'nullable|array',
        ]);

        $template = EmailTemplate::create($validated);

        return response()->json([
            'message' => 'Email template created successfully',
            'data' => EmailTemplateResource::make($template),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $template = EmailTemplate::with(['campaigns'])->findOrFail($id);

        return response()->json([
            'data' => EmailTemplateResource::make($template),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $template = EmailTemplate::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'default_subject' => 'nullable|string|max:255',
            'default_preview_text' => 'nullable|string',
            'html_content' => 'nullable|string',
            'text_content' => 'nullable|string',
            'available_variables' => 'nullable|array',
            'required_variables' => 'nullable|array',
            'thumbnail' => 'nullable|string|max:255',
            'design_config' => 'nullable|array',
            'type' => 'sometimes|in:transactional,marketing,automated,custom',
            'trigger_event' => 'nullable|in:order_placed,order_shipped,order_delivered,cart_abandoned,customer_registered,password_reset,product_back_in_stock,price_drop,review_request,manual,other',
            'trigger_conditions' => 'nullable|array',
            'trigger_delay_minutes' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'meta' => 'nullable|array',
        ]);

        $template->update($validated);

        return response()->json([
            'message' => 'Email template updated successfully',
            'data' => EmailTemplateResource::make($template->fresh()),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $template = EmailTemplate::findOrFail($id);
        $template->delete();

        return response()->json([
            'message' => 'Email template deleted successfully',
        ]);
    }

    // =========================================================================
    // TEMPLATE ACTIONS
    // =========================================================================

    public function render(Request $request, int $id): JsonResponse
    {
        $template = EmailTemplate::findOrFail($id);

        $validated = $request->validate([
            'variables' => 'nullable|array',
        ]);

        $variables = $validated['variables'] ?? [];

        // Validate required variables
        $missingVariables = $template->validateVariables($variables);
        if (!empty($missingVariables)) {
            return response()->json([
                'message' => 'Missing required variables',
                'missing_variables' => $missingVariables,
            ], 422);
        }

        $rendered = $template->render($variables);

        return response()->json([
            'data' => [
                'html' => $rendered,
                'variables_used' => $variables,
            ],
        ]);
    }

    public function preview(int $id): JsonResponse
    {
        $template = EmailTemplate::findOrFail($id);

        // Use placeholder values for preview
        $placeholders = [];
        foreach ($template->available_variables ?? [] as $variable) {
            $placeholders[$variable] = "[{$variable}]";
        }

        $rendered = $template->render($placeholders);

        return response()->json([
            'data' => [
                'html' => $rendered,
                'subject' => $template->default_subject,
                'preview_text' => $template->default_preview_text,
            ],
        ]);
    }

    public function duplicate(int $id): JsonResponse
    {
        $template = EmailTemplate::findOrFail($id);

        $newTemplate = $template->replicate();
        $newTemplate->name = $template->name . ' (Copy)';
        $newTemplate->slug = null; // Will auto-generate
        $newTemplate->is_default = false;
        $newTemplate->save();

        return response()->json([
            'message' => 'Template duplicated successfully',
            'data' => EmailTemplateResource::make($newTemplate),
        ], 201);
    }

    public function setAsDefault(int $id): JsonResponse
    {
        $template = EmailTemplate::findOrFail($id);

        // Remove default flag from other templates with same trigger event
        if ($template->trigger_event) {
            EmailTemplate::where('store_id', $template->store_id)
                ->where('trigger_event', $template->trigger_event)
                ->where('id', '!=', $template->id)
                ->update(['is_default' => false]);
        }

        $template->update(['is_default' => true]);

        return response()->json([
            'message' => 'Template set as default successfully',
            'data' => EmailTemplateResource::make($template->fresh()),
        ]);
    }

    public function activate(int $id): JsonResponse
    {
        $template = EmailTemplate::findOrFail($id);
        $template->update(['is_active' => true]);

        return response()->json([
            'message' => 'Template activated successfully',
            'data' => EmailTemplateResource::make($template->fresh()),
        ]);
    }

    public function deactivate(int $id): JsonResponse
    {
        $template = EmailTemplate::findOrFail($id);
        $template->update(['is_active' => false]);

        return response()->json([
            'message' => 'Template deactivated successfully',
            'data' => EmailTemplateResource::make($template->fresh()),
        ]);
    }
}
