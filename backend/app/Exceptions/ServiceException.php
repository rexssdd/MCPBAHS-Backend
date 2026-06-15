<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * Thrown when a service operation fails
 * Returns 500 Internal Server Error
 */
class ServiceException extends Exception
{
    private ?string $errorCode;

    public function __construct(string $message = 'Service operation failed', ?string $errorCode = null)
    {
        parent::__construct($message);
        $this->errorCode = $errorCode;
    }

    public function render(): JsonResponse
    {
        $response = [
            'message' => $this->message,
            'status' => 'error',
        ];

        if ($this->errorCode) {
            $response['error_code'] = $this->errorCode;
        }

        return response()->json($response, 500);
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }
}
