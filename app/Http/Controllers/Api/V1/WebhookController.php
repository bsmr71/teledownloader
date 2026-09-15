<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Services\Payment\PaymentManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handle(string $gateway, Request $request)
    {
        Log::info("Webhook received from {$gateway}:", $request->all());

        try {
            $service = PaymentManager::make($gateway);
            $result = $service->handleWebhook($request);

            if (empty($result['order_id'])) {
                return response()->json(['message' => 'Order ID not found'], 400);
            }

            $transaction = Transaction::with(['plan', 'user'])
                ->where('order_id', $result['order_id'])
                ->first();

            if (! $transaction) {
                return response()->json(['message' => 'Transaction not found'], 404);
            }

            if ($result['is_paid'] && ! $transaction->isPaid()) {
                $transaction->update([
                    'payment_status' => 'paid',
                    'paid_at' => now(),
                ]);

                // Create or extend subscription
                $plan = $transaction->plan;
                $durationDays = $plan->duration_days;
                $expiresAt = $durationDays ? now()->addDays($durationDays) : null;

                $sub = Subscription::create([
                    'user_id' => $transaction->user_id,
                    'plan_id' => $plan->id,
                    'starts_at' => now(),
                    'expires_at' => $expiresAt,
                    'status' => 'active',
                ]);

                Log::info("Subscription activated for order {$transaction->order_id}, License: {$sub->license_key}");
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error("Webhook error for {$gateway}: ".$e->getMessage());

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
