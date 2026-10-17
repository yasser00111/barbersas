<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    public function __construct(protected PaymentService $payments) {}

    public function handle(Request $request, string $gateway): JsonResponse
    {
        $event = $this->payments->handleWebhook($gateway, $request);

        // Providers only require a 2xx acknowledgement. We never trust the
        // frontend, and the signature/amount are validated server-side.
        return response()->json([
            'received' => true,
            'status' => $event->status,
        ], $event->status === 'rejected' ? 202 : 200);
    }
}
