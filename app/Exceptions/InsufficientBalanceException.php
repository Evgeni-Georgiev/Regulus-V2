<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class InsufficientBalanceException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'error' => 'Error: Insufficient balance',
            'message' => $this->getMessage(),
        ], Response::HTTP_BAD_REQUEST);
    }
}
