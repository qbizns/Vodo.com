<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use VodoCommerce\Http\Resources\EmailListResource;
use VodoCommerce\Models\EmailList;

class EmailListController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = EmailList::query();

        // Filter by store
        if ($request->has('store_id')) {
            $query->forStore((int) $request->get('store_id'));
        }

        // Filter by type
        if ($request->has('type')) {
            $query->ofType($request->get('type'));
        }

        // Filter active lists
        if ($request->boolean('active_only')) {
            $query->active();
        }

        // Filter by minimum health score
        if ($request->has('min_health_score')) {
            $query->withMinimumHealthScore((float) $request->get('min_health_score'));
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

        return EmailListResource::collection(
            $query->paginate($request->get('per_page', 15))
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'store_id' => 'required|exists:commerce_stores,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:static,dynamic,segment,tag,import',
            'criteria' => 'nullable|array',
            'is_active' => 'boolean',
            'allow_public_signup' => 'boolean',
            'welcome_message' => 'nullable|string',
            'send_welcome_email' => 'boolean',
            'welcome_email_template_id' => 'nullable|exists:commerce_email_templates,id',
            'require_double_optin' => 'boolean',
            'confirmation_email_template_id' => 'nullable|exists:commerce_email_templates,id',
            'meta' => 'nullable|array',
        ]);

        $list = EmailList::create($validated);

        return response()->json([
            'message' => 'Email list created successfully',
            'data' => EmailListResource::make($list),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $list = EmailList::with([
            'welcomeTemplate',
            'confirmationTemplate',
            'subscribers',
            'campaigns',
        ])->findOrFail($id);

        return response()->json([
            'data' => EmailListResource::make($list),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $list = EmailList::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|in:static,dynamic,segment,tag,import',
            'criteria' => 'nullable|array',
            'is_active' => 'boolean',
            'allow_public_signup' => 'boolean',
            'welcome_message' => 'nullable|string',
            'send_welcome_email' => 'boolean',
            'welcome_email_template_id' => 'nullable|exists:commerce_email_templates,id',
            'require_double_optin' => 'boolean',
            'confirmation_email_template_id' => 'nullable|exists:commerce_email_templates,id',
            'meta' => 'nullable|array',
        ]);

        $list->update($validated);

        return response()->json([
            'message' => 'Email list updated successfully',
            'data' => EmailListResource::make($list->fresh()),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $list = EmailList::findOrFail($id);
        $list->delete();

        return response()->json([
            'message' => 'Email list deleted successfully',
        ]);
    }

    // =========================================================================
    // LIST MANAGEMENT ACTIONS
    // =========================================================================

    public function addSubscriber(Request $request, int $id): JsonResponse
    {
        $list = EmailList::findOrFail($id);

        $validated = $request->validate([
            'email' => 'required|email',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'customer_id' => 'nullable|exists:commerce_customers,id',
            'source' => 'nullable|in:manual,import,api,signup_form,checkout,account_creation,other',
            'preferences' => 'nullable|array',
            'custom_fields' => 'nullable|array',
        ]);

        $subscriber = $list->addSubscriber(
            $validated['email'],
            $validated['first_name'] ?? null,
            $validated['last_name'] ?? null,
            $validated['customer_id'] ?? null,
            $validated['source'] ?? 'manual',
            $validated['preferences'] ?? null,
            $validated['custom_fields'] ?? null
        );

        return response()->json([
            'message' => 'Subscriber added successfully',
            'data' => $subscriber,
        ], 201);
    }

    public function syncDynamicList(int $id): JsonResponse
    {
        $list = EmailList::findOrFail($id);

        if ($list->type !== 'dynamic') {
            return response()->json([
                'message' => 'Only dynamic lists can be synced',
            ], 422);
        }

        $count = $list->syncDynamicList();

        return response()->json([
            'message' => 'Dynamic list synced successfully',
            'data' => [
                'subscribers_added' => $count,
                'last_synced_at' => $list->fresh()->last_synced_at,
            ],
        ]);
    }

    public function calculateEngagementMetrics(int $id): JsonResponse
    {
        $list = EmailList::findOrFail($id);
        $list->calculateEngagementMetrics();

        return response()->json([
            'message' => 'Engagement metrics calculated successfully',
            'data' => EmailListResource::make($list->fresh()),
        ]);
    }

    public function cleanInactiveSubscribers(Request $request, int $id): JsonResponse
    {
        $list = EmailList::findOrFail($id);

        $validated = $request->validate([
            'inactive_days' => 'nullable|integer|min:30',
        ]);

        $inactiveDays = $validated['inactive_days'] ?? 365;
        $removed = $list->cleanInactiveSubscribers($inactiveDays);

        return response()->json([
            'message' => 'Inactive subscribers cleaned successfully',
            'data' => [
                'removed_count' => $removed,
                'inactive_days' => $inactiveDays,
            ],
        ]);
    }

    public function importSubscribers(Request $request, int $id): JsonResponse
    {
        $list = EmailList::findOrFail($id);

        $validated = $request->validate([
            'subscribers' => 'required|array',
            'subscribers.*.email' => 'required|email',
            'subscribers.*.first_name' => 'nullable|string|max:255',
            'subscribers.*.last_name' => 'nullable|string|max:255',
            'subscribers.*.custom_fields' => 'nullable|array',
        ]);

        $imported = 0;
        $skipped = 0;

        foreach ($validated['subscribers'] as $subscriberData) {
            try {
                $list->addSubscriber(
                    $subscriberData['email'],
                    $subscriberData['first_name'] ?? null,
                    $subscriberData['last_name'] ?? null,
                    null,
                    'import',
                    null,
                    $subscriberData['custom_fields'] ?? null
                );
                $imported++;
            } catch (\Exception $e) {
                $skipped++;
            }
        }

        return response()->json([
            'message' => 'Subscribers imported successfully',
            'data' => [
                'imported' => $imported,
                'skipped' => $skipped,
                'total' => count($validated['subscribers']),
            ],
        ]);
    }

    public function exportSubscribers(int $id): JsonResponse
    {
        $list = EmailList::findOrFail($id);

        $subscribers = $list->subscribers()
            ->where('status', 'subscribed')
            ->get()
            ->map(function ($subscriber) {
                return [
                    'email' => $subscriber->email,
                    'first_name' => $subscriber->first_name,
                    'last_name' => $subscriber->last_name,
                    'subscribed_at' => $subscriber->subscribed_at?->toIso8601String(),
                    'engagement_score' => $subscriber->getEngagementScore(),
                    'custom_fields' => $subscriber->custom_fields,
                ];
            });

        return response()->json([
            'data' => $subscribers,
        ]);
    }

    // =========================================================================
    // ANALYTICS & REPORTING
    // =========================================================================

    public function analytics(int $id): JsonResponse
    {
        $list = EmailList::findOrFail($id);

        return response()->json([
            'data' => [
                'list_id' => $list->id,
                'name' => $list->name,
                'type' => $list->type,
                'statistics' => [
                    'total_subscribers' => $list->total_subscribers,
                    'active_subscribers' => $list->active_subscribers,
                    'unsubscribed_count' => $list->unsubscribed_count,
                    'bounced_count' => $list->bounced_count,
                    'complained_count' => $list->complained_count,
                ],
                'engagement' => [
                    'avg_open_rate' => (float) $list->avg_open_rate,
                    'avg_click_rate' => (float) $list->avg_click_rate,
                    'health_score' => $list->getHealthScore(),
                ],
                'growth' => [
                    'subscription_rate' => $list->total_subscribers > 0
                        ? ($list->active_subscribers / $list->total_subscribers) * 100
                        : 0,
                    'churn_rate' => $list->total_subscribers > 0
                        ? ($list->unsubscribed_count / $list->total_subscribers) * 100
                        : 0,
                ],
            ],
        ]);
    }
}
