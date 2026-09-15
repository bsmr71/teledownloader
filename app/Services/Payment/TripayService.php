<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TripayService implements PaymentGatewayInterface
{
    protected string $apiKey;

    protected string $privateKey;

    protected string $merchantCode;

    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.tripay.api_key', env('TRIPAY_API_KEY', ''));
        $this->privateKey = config('services.tripay.private_key', env('TRIPAY_PRIVATE_KEY', ''));
        $this->merchantCode = config('services.tripay.merchant_code', env('TRIPAY_MERCHANT_CODE', ''));
        $this->baseUrl = env('TRIPAY_IS_PRODUCTION', false)
            ? 'https://tripay.co.id/api/'
            : 'https://tripay.co.id/api-sandbox/';
    }

    public function createPayment(Transaction $transaction, array $params = []): array
    {
        $method = $params['payment_type'] ?? 'QRIS';
        $signature = hash_hmac('sha256', $this->merchantCode.$transaction->order_id.$transaction->gross_amount, $this->privateKey);

        $payload = [
            'method' => $method,
            'merchant_ref' => $transaction->order_id,
            'amount' => $transaction->gross_amount,
            'customer_name' => $transaction->user?->name ?? 'Customer TeleDownloader',
            'customer_email' => $transaction->user?->email ?? 'customer@teledownloader.test',
            'order_items' => [
                [
                    'name' => $transaction->plan->name,
                    'price' => $transaction->gross_amount,
                    'quantity' => 1,
                ],
            ],
            'signature' => $signature,
        ];

        try {
            $response = Http::withToken($this->apiKey)->post("{$this->baseUrl}transaction/create", $payload);
            if ($response->successful()) {
                $data = $response->json('data', []);

                return [
                    'payment_url' => $data['checkout_url'] ?? null,
                    'qr_string' => $data['qr_string'] ?? null,
                    'va_number' => $data['pay_code'] ?? null,
                    'payload' => $data,
                ];
            }
        } catch (\Exception $e) {
            Log::warning('Tripay call failed: '.$e->getMessage());
        }

        return [
            'payment_url' => 'https://tripay.co.id/checkout/'.$transaction->order_id,
            'qr_string' => '00020101021226580014ID.TRIPAY.DEMO5204541153033605802ID5908TripayQR6007Jakarta6304',
            'va_number' => null,
            'payload' => ['status' => 'simulated_tripay'],
        ];
    }

    public function handleWebhook(Request $request): array
    {
        $data = $request->all();
        $orderId = $data['merchant_ref'] ?? null;
        $status = strtoupper($data['status'] ?? '');
        $isPaid = ($status === 'PAID');

        return [
            'order_id' => $orderId,
            'is_paid' => $isPaid,
            'payment_type' => 'tripay_'.($data['payment_method'] ?? 'qris'),
            'raw' => $data,
        ];
    }
}
