<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Transaction;
use App\Services\Payment\PaymentManager;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function getPlans()
    {
        $plans = Plan::where('is_active', true)->get();

        return response()->json(['success' => true, 'plans' => $plans]);
    }

    public function createTransaction(Request $request)
    {
        $validated = $request->validate([
            'plan_code' => 'required|string|exists:plans,code',
            'payment_gateway' => 'nullable|string|in:bri,midtrans,tripay',
            'payment_type' => 'nullable|string', // qris, briva, etc.
            'customer_name' => 'nullable|string',
            'customer_email' => 'nullable|email',
        ]);

        $user = $request->user('sanctum');
        $plan = Plan::where('code', $validated['plan_code'])->firstOrFail();
        $gateway = $validated['payment_gateway'] ?? env('PAYMENT_DEFAULT_DRIVER', 'bri');
        $paymentType = $validated['payment_type'] ?? 'qris';

        $orderId = 'TLD-'.strtoupper(Str::random(6)).'-'.time();

        $transaction = Transaction::create([
            'user_id' => $user?->id,
            'plan_id' => $plan->id,
            'order_id' => $orderId,
            'gross_amount' => $plan->price,
            'payment_gateway' => $gateway,
            'payment_type' => $paymentType,
            'payment_status' => 'pending',
        ]);

        $service = PaymentManager::make($gateway);
        $paymentRes = $service->createPayment($transaction, [
            'payment_type' => $paymentType,
            'customer_name' => $validated['customer_name'] ?? $user?->name,
            'customer_email' => $validated['customer_email'] ?? $user?->email,
        ]);

        $transaction->update([
            'payment_url' => $paymentRes['payment_url'] ?? null,
            'qr_string' => $paymentRes['qr_string'] ?? null,
            'va_number' => $paymentRes['va_number'] ?? null,
            'payload' => $paymentRes['payload'] ?? [],
        ]);

        return response()->json([
            'success' => true,
            'order_id' => $orderId,
            'amount' => $transaction->gross_amount,
            'plan' => $plan->name,
            'payment_gateway' => $gateway,
            'payment_url' => $transaction->payment_url,
            'qr_string' => $transaction->qr_string,
            'va_number' => $transaction->va_number,
        ]);
    }
}
