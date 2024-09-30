<?php

namespace App\Http\Controllers\Api\Log;

use App\Http\Controllers\API\ApiController;
use App\Models\Log\Log;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogController extends ApiController
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $instance = $this->applyFilter(Log::query());

        return $this->response->mergeData($this->paginate(
            $instance->paginate($request->input('page_limit', 10))
        ))->send();
    }

    /**
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $record = Log::find($id);

            if (!$record) {
                $this->response->setStatusCode(404);
                throw new Exception('record_not_found');
            }

            return $this->response->setData($record)->send();
        } catch (Exception $e) {
            return $this->response->setStatus(false)->setMessage($e->getMessage())->setException($e)->send();
        }
    }
}
