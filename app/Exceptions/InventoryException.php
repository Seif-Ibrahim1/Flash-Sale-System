<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryException extends Exception
{
    public static function insufficientStock(string $id): self
    {
        return new self("Insufficient stock for product {$id}.", 409);
    }

    public static function holdExpired(): self
    {
        return new self("The hold has expired.", 400);
    }

    public static function holdAlreadyUsed(): self
    {
        return new self("This hold has already been used for an order.", 409);
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'error' => $this->getMessage(),
            'code' => $this->getCode(),
        ], $this->getCode());
    }
}
