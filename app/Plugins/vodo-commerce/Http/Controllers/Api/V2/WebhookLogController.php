<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use VodoCommerce\Models\Store;
use VodoCommerce\Models\WebhookLog;

class WebhookLogController extends Controller
{
    protected Store $store;

    public function __construct()
    {
        $this->store = resolve_store();
    }

    /**
     * List webhook logs.
     */
    public function index(Request $request): JsonResponse
    {
        $query = WebhookLog::where('store_id', $this->store->id)
            ->with(['subscription', 'event', 'delivery']);

        if ($request->has('level')) {
            $query->where('level', $request->level);
        }

        if ($request->has('subscription_id')) {
            $query->where('subscription_id', $request->subscription_id);
        }

        if ($request->has('event_id')) {
            $query->where('event_id', $request->event_id);
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        $perPage = $request->input('per_page', 50);
        $logs = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
                'last_page' => $logs->lastPage(),
            ],
        ]);
    }

    /**
     * Get log details.
     */
    public function show(int $id): JsonResponse
    {
        $log = WebhookLog::where('store_id', $this->store->id)
            ->with(['subscription', 'event', 'delivery'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $log,
        ]);
    }

    /**
     * Get log statistics by level.
     */
    public function statistics(): JsonResponse
    {
        $stats = WebhookLog::where('store_id', $this->store->id)
            ->selectRaw('level, COUNT(*) as count')
            ->groupBy('level')
            ->get()
            ->pluck('count', 'level');

        return response()->json([
            'success' => true,
            'data' => [
                'debug' => $stats['debug'] ?? 0,
                'info' => $stats['info'] ?? 0,
                'warning' => $stats['warning'] ?? 0,
                'error' => $stats['error'] ?? 0,
                'critical' => $stats['critical'] ?? 0,
                'total' => $stats->sum(),
            ],
        ]);
    }
}
