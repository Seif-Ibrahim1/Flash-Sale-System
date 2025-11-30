<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Payments\ProcessWebhookAction;
use App\Http\Requests\WebhookRequest;
use Illuminate\Http\JsonResponse;

class PaymentWebhookController extends Controller
{
    public function handle(WebhookRequest $request, ProcessWebhookAction $action): JsonResponse
    {
        // Data is already validated here
        $response = $action->handle(
            $request->validated('idempotency_key'),
            $request->validated() // Pass strict array
        );

        return response()->json($response);
    }
}
