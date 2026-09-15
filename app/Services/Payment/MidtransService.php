<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MidtransService implements PaymentGatewayInterface
{
    protected string $serverKey;

    protected string $clientKey;

    protected bool $isProduction;

    protected string $snapUrl;

    public function __construct()
    {
        $this->serverKey = config('services.midtrans.server_key', env('MIDTRANS_SERVER_KEY', 'SB-Mid-server-demo'));
        $this->clientKey = config('services.midtrans.client_key', env('MIDTRANS_CLIENT_KEY', 'SB-Mid-client-demo'));
        $this->isProduction = (bool) config('services.midtrans.is_production', env('MIDTRANS_IS_PRODUCTION', false));
        $this->snapUrl = $this->isProduction
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';
    }

    public function createPayment(Transaction $transaction, array $params = []): array
    {
        $payload = [
            'transaction_details' => [
                'order_id' => $transaction->order_id,
                'gross_amount' => $transaction->gross_amount,
            ],
            'item_details' => [
                [
                    'id' => $transaction->plan->code,
                    'price' => $transaction->gross_amount,
                    'quantity' => 1,
                    'name' => $transaction->plan->name,
                ],
            ],
            'customer_details' => [
                'first_name' => $transaction->user?->name ?? 'User Tele Downloader',
                'email' => $transaction->user?->email ?? 'customer@teledownloader.test',
            ],
            'enabled_payments' => ['qris', 'gopay', 'shopeepay', 'bca_va', 'bni_va', 'bri_va'],
        ];

        try {
            $response = Http::withBasicAuth($this->serverKey, '')
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->snapUrl, $payload);

            if ($response->successful()) {
                $resData = $response->json();

                return [
                    'payment_url' => $resData['redirect_url'] ?? null,
                    'qr_string' => null,
                    'va_number' => null,
                    'payload' => $resData,
                ];
            }
        } catch (\Exception $e) {
            Log::warning('Midtrans call failed: '.$e->getMessage());
        }

        return [
            'payment_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/'.$transaction->order_id,
            'qr_string' => null,
            'va_number' => null,
            'payload' => ['status' => 'simulated_midtrans'],
        ];
    }

    public function handleWebhook(Request $request): array
    {
        $data = $request->all();
        $orderId = $data['order_id'] ?? null;
        $statusCode = $data['status_code'] ?? '';
        $transactionStatus = $data['transaction_status'] ?? '';

        $isPaid = false;
        if ($transactionStatus === 'capture' || $transactionStatus === 'settlement') {
            $isPaid = true;
        }

        return [
            'order_id' => $orderId,
            'is_paid' => $isPaid,
            'payment_type' => 'midtrans_'.($data['payment_type'] ?? 'unknown'),
            'raw' => $data,
        ];
    }
}
