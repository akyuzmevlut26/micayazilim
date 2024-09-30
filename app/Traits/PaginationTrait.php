<?php

namespace App\Traits;

trait PaginationTrait
{
    /**
     * @param $collection
     * @return array
     */
    public function paginate($collection): array
    {
        if (method_exists($collection, 'items')) {
            $response = [
                'data' => $collection->items(),
                'meta' => [
                    'current_page' => (int)$collection->currentPage(),
                    'per_page' => (int)$collection->perPage(),
                    'total_page' => (int)$collection->lastPage(),
                    'total_record' => (int)$collection->total(),
                    'has_more_page' => ($collection->lastPage() > $collection->currentPage())
                ],
                'links' => [
                    'prev' => $collection->previousPageUrl(),
                    'next' => $collection->nextPageUrl(),
                    'total' => $collection->getUrlRange(1, $collection->lastPage())
                ]
            ];
        }

        return $response ?? [];
    }
}
