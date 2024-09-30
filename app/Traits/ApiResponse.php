<?php

namespace App\Traits;

use App\Http\Response\CustomResponse;

trait ApiResponse
{
    /**
     * @var CustomResponse
     */
    public CustomResponse $response;

    /**
     * ApiResponse constructor.
     */
    public function __construct()
    {
        $this->response = new CustomResponse();
    }
}
