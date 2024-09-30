<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use App\Traits\PaginationTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApiController extends Controller
{
    use ApiResponse, PaginationTrait;

    /**
     * @param $collection
     * @param array $disabled
     * @return mixed
     */
    public function applyFilter($collection, array $disabled = []): mixed
    {
        $id = request()->input('id');
        if ($id > 0) {
            $collection->where('id', $id);
        }

        if (($ids = request()->input('ids')) && count($ids)) {
            $collection->whereIn('id', $ids);
        }

        if (request()->has('title')) {
            $collection->where('title', 'LIKE', '%' . request()->input('title') . '%');
        }

        if (request()->has('created_at')) {
            $createdMin = request()->input('created_at.min');
            $createdMax = request()->input('created_at.max');

            if (strlen($createdMin)) {
                $collection->whereDate('created_at', '>=', Carbon::parse($createdMin)->format('Y-m-d'));
            }

            if (strlen($createdMax)) {
                $collection->whereDate('created_at', '<=', Carbon::parse($createdMax)->format('Y-m-d'));
            }
        }

        if (request()->has('sort')) {
            $params = [];

            $sortArr = explode(',', request()->input('sort'));

            foreach ($sortArr as $sort) {
                $explode = explode(':', $sort);
                if (count($explode) == 2) {
                    list($column, $direction) = $explode;

                    if ($column != null && $direction !== null) {
                        $params[] = [
                            'column' => $column,
                            'direction' => $direction
                        ];
                    }
                }
            }

            foreach ($params as $param) {
                $collection->orderBy($param['column'], $param['direction']);
            }
        }

        $withTrashed = request()->input('with_trashed', false);
        if ($withTrashed) {
            $collection->withTrashed();
        }

        return $collection;
    }
}
