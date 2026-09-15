<?php

namespace App\Contracts;

use App\Models\Transaction;
use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    /**
     * Create payment request with the gateway (QRIS / BRIVA / Snap)
     * Returns: ['payment_url' => string, 'qr_string' => ?string, 'va_number' => ?string, 'payload' => array]
     */
    public function createPayment(Transaction $transaction, array $params = []): array;

    /**
     * Process webhook callback and return status
     * Returns: ['order_id' => string, 'is_paid' => bool, 'payment_type' => string, 'raw' => array]
     */
    public function handleWebhook(Request $request): array;
}
