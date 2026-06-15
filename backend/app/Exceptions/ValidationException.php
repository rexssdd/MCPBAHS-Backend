<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * Custom validation exception for meaningful error responses
 * Returns 422 Unprocessable Entity with validation errors
 */
class ValidationException extends Exception
{
    private array $errors;

    public function __construct(string $message = 'Validation failed', array $errors = [], int $code = 0)
    {
        parent::__construct($message, $code);
        $this->errors = $errors;
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->message,
            'errors' => $this->errors,
        ], 422);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
