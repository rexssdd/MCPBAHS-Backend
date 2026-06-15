<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class InvitationAlreadyAcceptedException extends Exception
{
    public function render() : JsonResponse
    {
        return response()->json(['message' => 'This invitation has already been accepted.'], 409);
    }
}
