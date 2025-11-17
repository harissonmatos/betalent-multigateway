<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Gateway;
use InvalidArgumentException;

class PaymentGatewayManager
{
    public function __construct(
        private Gateway1Client $gateway1,
        private Gateway2Client $gateway2,
    ) {
    }

    public function driver(string $name): PaymentGatewayInterface
    {
        return match ($name) {
            'gateway1' => $this->gateway1,
            'gateway2' => $this->gateway2,
            default => throw new InvalidArgumentException("Gateway [{$name}] não suportado."),
        };
    }
}
