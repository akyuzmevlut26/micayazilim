<?php

namespace App\Helpers;

use Exception;

class Curl
{
    /**
     * @param array $data
     * @return array
     */
    public function request(array $data = [], string $method = 'GET'): array
    {
        try {
            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => $data['url'],
                CURLOPT_ENCODING => "gzip",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_TIMEOUT => 20
            ]);

            if ($header = ($data['header'] ?? null)) {
                curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
            } else {
                curl_setopt($curl, CURLOPT_HTTPHEADER, [
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36'
                ]);
            }

            if ($send = ($data['payload'] ?? null)) {
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $send);
            }

            $response = curl_exec($curl);
            curl_close($curl);

            if (!$response) {
                throw new Exception(curl_error($curl));
            }

            if (curl_getinfo($curl, CURLINFO_HTTP_CODE) !== 200) {
                throw new Exception($response);
            }

            return ['status' => true, 'data' => $response];
        } catch (Exception $e) {
            return ['status' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * @param $urls
     * @return array
     */
    public static function multiRequest(array $urls = []): array
    {
        $mh = curl_multi_init();
        $curl_array = [];


        foreach ($urls as $key => $data) {
            $curl_array[$key] = curl_init($data['url']);

            curl_setopt($curl_array[$key], CURLOPT_ENCODING, "gzip");
            curl_setopt($curl_array[$key], CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.132 Safari/537.36");
            curl_setopt($curl_array[$key], CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl_array[$key], CURLOPT_TIMEOUT, 20);

            if ($header = ($data['header'] ?? null)) {
                curl_setopt($curl_array[$key], CURLOPT_HTTPHEADER, $header);
            } else {
                curl_setopt($curl_array[$key], CURLOPT_HTTPHEADER, [
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36'
                ]);
            }

            if ($send = ($data['payload'] ?? null)) {
                curl_setopt($curl_array[$key], CURLOPT_POST, 1);
                curl_setopt($curl_array[$key], CURLOPT_POSTFIELDS, $send);
            }

            curl_multi_add_handle($mh, $curl_array[$key]);
        }

        do {
            curl_multi_exec($mh, $running);
        } while ($running > 0);

        $response = [];

        foreach ($urls as $key => $data) {
            $curlResponse = (curl_multi_getcontent($curl_array[$key]) != null ? curl_multi_getcontent($curl_array[$key]) : null);

            $response[$key] = [
                'item' => $key,
                'status_code' => curl_getinfo($curl_array[$key], CURLINFO_HTTP_CODE),
                'response' => $curlResponse
            ];

            curl_multi_remove_handle($mh, $curl_array[$key]);
            curl_close($curl_array[$key]);
        }

        curl_multi_close($mh);

        return $response;
    }

}

