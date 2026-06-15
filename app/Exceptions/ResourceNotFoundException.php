<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * Thrown when a resource is not found
 * Returns 404 Not Found
 */
class ResourceNotFoundException extends Exception
{
    public function __construct(string $resource = 'Resource', string $identifier = '')
    {
        $message = $identifier 
            ? "{$resource} with ID {$identifier} not found"
            : "{$resource} not found";
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->message,
            'status' => 'error',
        ], 404);
    }
}
