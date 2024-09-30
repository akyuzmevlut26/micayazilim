<?php

namespace App\Jobs;

use App\Classes\Trendyol\Trendyol;
use App\Models\Log\Log;
use App\Models\Product\Product;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TrendyolJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var array|int[]
     */
    private array $options = [
        'COMMIT_DATABASE' => true, //Commit every job to database
        'FETCH_PRODUCT_LIMIT' => 500, // Record
        'NEXT_RECORD_WAIT_TIMEOUT' => 10, //Second
        'RETRY_LIMIT' => 3 //Attempt
    ];

    /**
     * @var array
     */
    private array $fetchedProducts = [];

    /**
     * @var array
     */
    private array $requestProductFilters = [];

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->requestProductFilters = [
            'page' => 0,
            'size' => $this->getOption('FETCH_PRODUCT_LIMIT', 500)
        ];
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return 'trendyol';
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        // Fetch Products
        $this->fetchProducts();
        echo PHP_EOL;
    }

    /**
     * @param string $option
     * @param $default
     * @return int|mixed|null
     */
    private function getOption(string $option, $default = null): mixed
    {
        return array_key_exists($option, $this->options) ? $this->options[$option] : $default;
    }

    /**
     * @return void
     */
    private function fetchProducts(): void
    {
        echo "[Fetch Product] - Start!" . PHP_EOL;
        $startTime = microtime(true);

        try {
            if ($this->getOption('COMMIT_DATABASE', true)) {
                Product::truncate();
            }

            $fetchResult = $this->requestProducts();
            if (!$fetchResult['status']) {
                throw new Exception($fetchResult['error']);
            }

            $this->setFetchedProducts($fetchResult['data']['content']);
            sleep($this->getOption('NEXT_RECORD_WAIT_TIMEOUT', 10));

            if (isset($fetchResult['data']['totalPages']) && $fetchResult['data']['totalPages'] > 1) {
                for ($page = 1; $page < $fetchResult['data']['totalPages']; $page++) {
                    $this->setRequestProductFilters('page', $page);

                    $fetchResult = $this->requestProducts();
                    if (!$fetchResult['status']) {
                        throw new Exception($fetchResult['error']);
                    }

                    $this->setFetchedProducts($fetchResult['data']['content']);
                    sleep($this->getOption('NEXT_RECORD_WAIT_TIMEOUT', 10));
                }
            }

            $processTime = number_format((microtime(true) - $startTime), 3, '.', '');
            $this->log(Log::TYPE_INFO, sprintf("%d products were fetched successfully. (Process Time: %s sec)", count($this->fetchedProducts), $processTime));
        } catch (Exception $e) {
            $processTime = number_format((microtime(true) - $startTime), 3, '.', '');
            $this->log(Log::TYPE_ERROR, sprintf("Products could not fetch. (Process Time: %s sec, Error: %s)", $processTime, $e->getMessage()));
        }

        echo "[Fetch Product] - End!" . PHP_EOL;
    }

    /**
     * @param int $retryTryCount
     * @return array
     */
    private function requestProducts(int $retryTryCount = 0): array
    {
        try {
            $fetchRanges = [
                'start' => ($this->requestProductFilters['size'] * $this->requestProductFilters['page']),
                'end' => ($this->requestProductFilters['size'] * ($this->requestProductFilters['page'] + 1))
            ];

            $trendyol = new Trendyol();

            $requestResult = $trendyol->setFilters($this->requestProductFilters)->getProducts();
            if (!$requestResult['status']) {
                if ($retryTryCount < $this->getOption('RETRY_LIMIT', 3)) {
                    $this->log(Log::TYPE_INFO, sprintf("Products from %d to %d could not fetch, it will try to fetch again.", $fetchRanges['start'], $fetchRanges['end']));

                    return $this->requestProducts(($retryTryCount + 1));
                } else {
                    throw new Exception($requestResult['error']);
                }
            }

            $data = $requestResult['data'];
            $this->log(Log::TYPE_INFO, sprintf("Products from %d to %d were fetched. (Total: %d)", $fetchRanges['start'], $fetchRanges['end'], $data['totalElements']));

            return ['status' => true, 'data' => $data];
        } catch (Exception $e) {
            $this->log(Log::TYPE_ERROR, sprintf('An error occurred while requesting data. (Error: %s)', $e->getMessage()));

            return ['status' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    private function setRequestProductFilters(string $key, mixed $value): static
    {
        $this->requestProductFilters[$key] = $value;
        return $this;
    }

    /**
     * @param array $data
     * @return $this
     */
    private function setFetchedProducts(array $data): static
    {
        try {
            $fetchedRanges = [
                'start' => ($this->requestProductFilters['size'] * $this->requestProductFilters['page']),
                'end' => ($this->requestProductFilters['size'] * ($this->requestProductFilters['page'] + 1))
            ];

            $regularizedData = array_map(function ($data) {
                return [
                    'main_id' => $data['productMainId'],
                    'title' => $data['title'],
                    'barcode' => $data['barcode'],
                    'description' => $data['description'],
                    'sale_price' => $data['salePrice'],
                    'stock_unit' => $data['stockUnitType'],
                    'quantity' => $data['quantity'],
                    'attrs' => ((isset($data['attributes']) && is_array($data['attributes']) && count($data['attributes'])) ? json_encode($data['attributes']) : null),
                    'approved' => $data['approved'],
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ];
            }, $data);

            if ($this->getOption('COMMIT_DATABASE', true)) {
                Product::insert($regularizedData);
            }

            $this->fetchedProducts = array_merge($this->fetchedProducts, $regularizedData);
            $this->log(Log::TYPE_INFO, sprintf('Products from %d to %d were committed to database.', $fetchedRanges['start'], $fetchedRanges['end']));
        } catch (Exception $e) {
            $this->log(Log::TYPE_ERROR, sprintf('Products from %d to %d could not commit to database because of illegal data. (Error: %s)', $fetchedRanges['start'], $fetchedRanges['end'], $e->getMessage()));
        }

        return $this;
    }

    /**
     * @param string $type
     * @param string $message
     * @return void
     */
    private function log(string $type, string $message): void
    {
        Log::add($type, Log::RELATION_TYPE_TRENDYOL, $message);
        echo sprintf("[Fetch Product] - %s", $message) . PHP_EOL;
    }
}
