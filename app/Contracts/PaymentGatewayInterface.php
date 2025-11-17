<?php

namespace App\Contracts;

use App\DTOs\Payment\TransactionData;

interface PaymentGatewayInterface
{
    /**
     * Lista transações de forma padronizada.
     *
     * @return array
     */
    public function listTransactions(): array;

    /**
     * Cria uma transação.
     *
     * @return array Resposta (normalizada ou bruta, você decide)
     */
    public function createTransaction(TransactionData $data): array;

    /**
     * Reembolso/chargeback de uma transação.
     *
     * @return array
     */
    public function refund(string $transactionId): array;
}
