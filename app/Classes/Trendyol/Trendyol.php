<?php

namespace App\Classes\Trendyol;

use Exception;
use App\Helpers\Curl;

class Trendyol
{
    /**
     * @var string
     */
    private string $url = 'https://api.trendyol.com/sapigw';

    /**
     * @var string
     */
    private string $apiKey = 'm5Yw7mrhl6gzGqnRdUNm';

    /**
     * @var string
     */
    private string $apiSecret = 'pmdibpKG2BKsKQjJ0AQe';

    /**
     * @var int
     */
    private int $supplierId = 732323;

    /**
     * @var string|null
     */
    private string|null $apiUrl = null;

    /**
     * @var array
     */
    private array $filters = [];

    /**
     * @return string
     */
    public function getApiUrl(): string
    {
        return $this->apiUrl;
    }

    /**
     * @param string $urlPath
     * @return $this
     */
    public function setApiUrl(string $urlPath): static
    {
        $this->apiUrl = $this->url . $urlPath;
        return $this;
    }

    /**
     * @param array $filters
     * @return $this
     */
    public function setFilters(array $filters): static
    {
        $this->filters = $filters;
        return $this;
    }

    /**
     * @param string $filter
     * @param mixed|null $default
     * @return mixed
     */
    public function getFilter(string $filter, mixed $default = null): mixed
    {
        return $this->filters[$filter] ?? $default;
    }

    /**
     * @return array
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @return int
     */
    public function getSupplierId(): int
    {
        return $this->supplierId;
    }

    /**
     * @return string
     */
    private function getAuthorization(): string
    {
        return base64_encode(sprintf('%s:%s', $this->apiKey, $this->apiSecret));
    }

    /**
     * @return array
     */
    public function getProducts(): array
    {
        try {
            $this->setApiUrl(sprintf('/suppliers/%d/products', $this->getSupplierId()));

            $url = $this->getApiUrl() . ((count($this->getFilters())) ? '?' . http_build_query($this->getFilters()) : '');

            $curl = new Curl();

            $request = $curl->request([
                'url' => $url,
                'header' => [
                    sprintf('Authorization: Basic %s', $this->getAuthorization()),
                    sprintf('User-Agent: %d - SelfIntegration', $this->supplierId),
                    'Content-Type: application/json'
                ]
            ]);

            if (!$request['status']) {
                throw new Exception($request['error']);
            }

            return ['status' => true, 'data' => json_decode($request['data'], true)];
        } catch (Exception $e) {
            return ['status' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * @param string $barcode
     * @param string $salePrice
     * @param int $quantity
     * @return array|true[]
     */
    public function priceAndInventory(string $barcode, string $salePrice, int $quantity): array
    {
        try {
            $this->setApiUrl(sprintf('/suppliers/%d/products/price-and-inventory', $this->getSupplierId()));

            $curl = new Curl();

            $request = $curl->request([
                'url' => $this->getApiUrl(),
                'header' => [
                    sprintf('Authorization: Basic %s', $this->getAuthorization()),
                    sprintf('User-Agent: %d - SelfIntegration', $this->supplierId),
                    'Content-Type: application/json'
                ],
                'payload' => json_encode([
                    'items' => [
                        [
                            'barcode' => $barcode,
                            'salePrice' => $salePrice,
                            'quantity' => $quantity
                        ]
                    ]
                ])
            ], 'POST');

            if (!$request['status']) {
                throw new Exception($request['error']);
            }

            return ['status' => true];
        } catch (Exception $e) {
            return ['status' => false, 'error' => $e->getMessage()];
        }
    }
}
