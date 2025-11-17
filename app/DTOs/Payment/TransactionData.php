<?php

namespace App\DTOs\Payment;

class TransactionData
{
    public function __construct(
        public int $amount,
        public string $name,
        public string $email,
        public string $cardNumber,
        public string $cvv,
    ) {}
}
