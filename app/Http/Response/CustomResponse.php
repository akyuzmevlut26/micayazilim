<?php

namespace App\Http\Response;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use Throwable;

class CustomResponse
{
    /**
     * @var bool
     */
    private bool $status = true;

    /**
     * @var int
     */
    private int $statusCode = 200;

    /**
     * @var string|null
     */
    private ?string $message = null;

    /**
     * @var string|array|int
     */
    private $data;

    /**
     * @var array
     */
    private $mergeData;

    /**
     * @var string
     */
    private $dataResponseKey = 'data';

    /**
     * @var array
     */
    private $warnings = [];

    /**
     * @var array
     */
    private $errors = [];

    /**
     * @var Exception
     */
    private $exceptionHandler;

    /**
     * @var array
     */
    private $override = [];

    /**
     * @param bool $status
     * @param int|null $statusCode
     * @return $this
     */
    public function setStatus(bool $status, int $statusCode = null): CustomResponse
    {
        $this->status = $status;

        if ($statusCode) {
            $this->statusCode = $statusCode;
        }

        return $this;
    }

    /**
     * @param int $code
     * @return $this
     */
    public function setStatusCode(int $code): CustomResponse
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @param string $message
     * @return $this
     */
    public function setMessage(string $message): CustomResponse
    {
        $this->message = $message;
        return $this;
    }

    /**
     * @param $data
     * @param string|null $customKey
     * @return $this
     */
    public function setData($data, string $customKey = null): CustomResponse
    {
        $this->data = $data;

        if (!is_null($customKey)) {
            $this->dataResponseKey = $customKey;
        }

        return $this;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function mergeData(array $data): CustomResponse
    {
        $this->mergeData = $data;
        return $this;
    }

    /**
     * @param array $warning
     * @return $this
     */
    public function setWarnings(array $warning): CustomResponse
    {
        $this->warnings[] = $warning;
        return $this;
    }

    /**
     * @param array $error
     * @return $this
     */
    public function addError(array $error): static
    {
        $this->errors[] = $error;
        return $this;
    }

    /**
     * @param $errors
     * @return $this
     * @throws Exception
     */
    public function setErrors($errors): CustomResponse
    {
        if ($errors instanceof MessageBag) {
            $this->errors = $errors->toArray();
        } else {
            if (!(is_array($errors) || is_object($errors))) {
                throw new Exception('must_be_array_or_object');
            }

            $this->errors = $errors;
        }

        return $this;
    }

    /**
     * @param Exception|Throwable $exception
     * @return $this
     */
    public function setException(Exception|Throwable $exception): CustomResponse
    {
        $this->exceptionHandler = $exception;
        return $this;
    }

    /**
     * @param array|Collection $response
     * @return $this
     */
    public function overrideResponse(array|Collection $response): CustomResponse
    {
        $this->override = $response;
        return $this;
    }

    /**
     * @return JsonResponse
     */
    public function send(): JsonResponse
    {
        if ($this->override) {
            $message = $this->override;
        } else {
            $message = [
                'status' => $this->status,
                'status_code' => $this->statusCode,
                'message' => $this->message
            ];

            if (!is_null($this->mergeData)) {
                $this->data = null;
                $message = array_merge($message, $this->mergeData);
            }

            if (!is_null($this->data)) {
                $message[$this->dataResponseKey ?? 'data'] = $this->data;
            }

            if ($this->warnings) {
                $message['warnings'] = $this->warnings;
            }

            if (!$this->status) {
                $message['errors'] = $this->errors;

                if ($this->statusCode == 200) {
                    $this->statusCode = 400;
                    $message['status_code'] = $this->statusCode;
                }

                if ($this->exceptionHandler) {
                    $message['error_details'] = [
                        'file' => (config('app.debug', false) ? $this->exceptionHandler->getFile() : null),
                        'line' => $this->exceptionHandler->getLine(),
                        'code' => $this->exceptionHandler->getCode(),
                        'trace' => (config('app.debug', false) ? $this->exceptionHandler->getTrace() : null)
                    ];
                }
            }
        }

        return response()->json($message, $this->statusCode);
    }
}
