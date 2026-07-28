<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Base for versioned API controllers. Provides a consistent JSON envelope:
 *   { "data": ..., "meta"?: {...}, "message"?: "..." }
 */
abstract class ApiController extends Controller
{
    protected function ok(mixed $data = null, ?string $message = null, int $status = 200): JsonResponse
    {
        $payload = ['data' => $data instanceof JsonResource ? $data->resolve() : $data];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        return response()->json($payload, $status);
    }

    protected function message(string $message, int $status = 200): JsonResponse
    {
        return response()->json(['message' => $message], $status);
    }

    /** Wrap a paginator into the standard envelope with pagination meta. */
    protected function paginated(LengthAwarePaginator $paginator, ?callable $transform = null): JsonResponse
    {
        $items = collect($paginator->items());
        $data = $transform ? $items->map($transform)->all() : $items->all();

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    protected function collection(ResourceCollection $collection): JsonResponse
    {
        return response()->json(['data' => $collection->resolve()]);
    }
}
