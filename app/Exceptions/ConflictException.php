<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * Thrown when an operation conflicts with system state
 * Returns 409 Conflict
 */
class ConflictException extends Exception
{
    private string $resource;

    public function __construct(string $message = 'Operation conflicts with current state', string $resource = '')
    {
        parent::__construct($message);
        $this->resource = $resource;
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->message,
            'resource' => $this->resource,
            'status' => 'conflict',
        ], 409);
    }
}
