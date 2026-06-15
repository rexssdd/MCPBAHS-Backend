<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * Thrown when user lacks required permissions
 * Returns 403 Forbidden
 */
class AuthorizationException extends Exception
{
    public function __construct(string $action = 'perform this action', string $reason = '')
    {
        $message = "You are not authorized to {$action}";
        if ($reason) {
            $message .= ". {$reason}";
        }
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->message,
            'status' => 'forbidden',
        ], 403);
    }
}
