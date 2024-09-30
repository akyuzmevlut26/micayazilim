<?php

namespace App\Http\Controllers\Api\Product;

use App\Classes\Trendyol\Trendyol;
use App\Http\Controllers\API\ApiController;
use App\Jobs\TrendyolJob;
use App\Models\Product\Product;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

class ProductController extends ApiController
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {

        $instance = $this->applyFilter(Product::query());

        $isVariant = $request->input('is_variant');
        if ($isVariant != null && strlen($isVariant)) {
            $instance->where('attrs', (((int)$isVariant) === 0 ? '=' : '!='), null);
        }

        if (($barcode = $request->input('barcode')) && strlen($barcode)) {
            $instance->where('barcode', 'LIKE', '%' . $barcode . '%');
        }

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
            $record = Product::find($id);

            if (!$record) {
                $this->response->setStatusCode(404);
                throw new Exception('record_not_found');
            }

            return $this->response->setData($record)->send();
        } catch (Exception $e) {
            return $this->response->setStatus(false)->setMessage($e->getMessage())->setException($e)->send();
        }
    }

    /**
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     * @throws Throwable
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'quantity' => 'required|int|min:1',
                'sale_price' => 'required|numeric|min:0',
            ]);

            if ($validator->fails()) {
                $this->response->setStatusCode(422)->setErrors($validator->errors());
                throw new Exception('validate_error');
            }

            $record = Product::find($id);

            if (!$record) {
                $this->response->setStatusCode(404);
                throw new Exception('record_not_found');
            }

            $data = $validator->validated();

            $trendyol = new Trendyol();
            $requestResult = $trendyol->priceAndInventory($record['barcode'], $data['sale_price'], $data['quantity']);

            if (!$requestResult['status']) {
                throw new Exception($requestResult['error']);
            }

            $record->update($validator->validated());

            return $this->response->setStatusCode(200)->setData([
                'id' => $record->id,
                'action' => 'update'
            ])->send();
        } catch (Exception $e) {
            return $this->response->setStatus(false)->setMessage($e->getMessage())->setException($e)->send();
        }
    }

    /**
     * @return JsonResponse
     */
    public function refresh(): JsonResponse
    {
        TrendyolJob::dispatch();
        return $this->response->send();
    }
}
