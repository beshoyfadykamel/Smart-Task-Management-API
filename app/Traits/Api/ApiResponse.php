<?php

namespace App\Traits\Api;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

trait ApiResponse
{
    protected function success($data = null, $message = null, $code = 200)
    {
        return response()->json([
            'success' => true,
            'code'    => $code,
            'message' => $message,
            'data'    => $data,
            'errors'  => null,
        ], $code);
    }

    protected function error($message = null, $errors = null, $code = 400)
    {
        return response()->json([
            'success' => false,
            'code'    => $code,
            'message' => $message,
            'data'    => null,
            'errors'  => $errors,
        ], $code);
    }

    protected function successPaginated(
        LengthAwarePaginator $paginator,
        AnonymousResourceCollection $collection,
        string $resourceKey,
        ?string $message = null,
        int $code = 200
    ) {
        return $this->success([
            $resourceKey => $collection,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'links' => [
                    'first' => $paginator->url(1),
                    'last' => $paginator->url($paginator->lastPage()),
                    'prev' => $paginator->previousPageUrl(),
                    'next' => $paginator->nextPageUrl(),
                ],
            ],
        ], $message, $code);
    }
}
