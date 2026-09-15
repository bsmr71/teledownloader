<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;

class PaymentManager
{
    public static function make(string $gateway = 'bri'): PaymentGatewayInterface
    {
        return match (strtolower($gateway)) {
            'bri', 'briapi' => new BriApiService,
            'midtrans' => new MidtransService,
            'tripay' => new TripayService,
            default => new BriApiService,
        };
    }
}
