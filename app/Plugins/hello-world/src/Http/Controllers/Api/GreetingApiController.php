<?php

namespace HelloWorld\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use HelloWorld\Models\Greeting;
use HelloWorld\Http\Requests\StoreGreetingRequest;
use HelloWorld\Http\Requests\UpdateGreetingRequest;
use HelloWorld\Events\GreetingCreated;
use HelloWorld\Events\GreetingDeleted;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class GreetingApiController extends Controller
{
    /**
     * Display a listing of greetings.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Greeting::query();

        // Search
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('message', 'like', "%{$search}%")
                  ->orWhere('author', 'like', "%{$search}%");
            });
        }

        // Filter by author
        if ($request->has('author')) {
            $query->where('author', $request->input('author'));
        }

        // Sorting
        $sortField = $request->input('sort', 'created_at');
        $sortOrder = $request->input('order', 'desc');
        $query->orderBy($sortField, $sortOrder);

        // Pagination
        $perPage = min($request->input('per_page', 20), 100);
        $greetings = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $greetings->items(),
            'meta' => [
                'current_page' => $greetings->currentPage(),
                'per_page' => $greetings->perPage(),
                'total' => $greetings->total(),
                'total_pages' => $greetings->lastPage(),
                'from' => $greetings->firstItem(),
                'to' => $greetings->lastItem(),
            ],
            'links' => [
                'first' => $greetings->url(1),
                'last' => $greetings->url($greetings->lastPage()),
                'prev' => $greetings->previousPageUrl(),
                'next' => $greetings->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Store a newly created greeting.
     *
     * @param StoreGreetingRequest $request
     * @return JsonResponse
     */
    public function store(StoreGreetingRequest $request): JsonResponse
    {
        $greeting = Greeting::create([
            'message' => $request->input('message'),
            'author' => $request->input('author', 'Anonymous'),
        ]);

        event(new GreetingCreated($greeting));

        return response()->json([
            'success' => true,
            'data' => $greeting,
            'message' => 'Greeting created successfully',
        ], 201);
    }

    /**
     * Display the specified greeting.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $greeting = Greeting::find($id);

        if (!$greeting) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'GREETING_NOT_FOUND',
                    'message' => 'The requested greeting was not found',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $greeting,
        ]);
    }

    /**
     * Update the specified greeting.
     *
     * @param UpdateGreetingRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateGreetingRequest $request, int $id): JsonResponse
    {
        $greeting = Greeting::find($id);

        if (!$greeting) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'GREETING_NOT_FOUND',
                    'message' => 'The requested greeting was not found',
                ],
            ], 404);
        }

        $greeting->update($request->validated());

        return response()->json([
            'success' => true,
            'data' => $greeting->fresh(),
            'message' => 'Greeting updated successfully',
        ]);
    }

    /**
     * Remove the specified greeting.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $greeting = Greeting::find($id);

        if (!$greeting) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'GREETING_NOT_FOUND',
                    'message' => 'The requested greeting was not found',
                ],
            ], 404);
        }

        $greetingId = $greeting->id;
        $greeting->delete();

        event(new GreetingDeleted($greetingId));

        return response()->json([
            'success' => true,
            'message' => 'Greeting deleted successfully',
        ]);
    }
}
