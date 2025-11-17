<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\DTOs\Payment\TransactionData;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class Gateway2Client implements PaymentGatewayInterface
{
    private string $baseUrl;
    private string $authToken;
    private string $authSecret;

    public function __construct()
    {
        $config = config('gateways.gateway2');

        $this->baseUrl    = rtrim($config['base_url'], '/');
        $this->authToken  = $config['auth_token'];
        $this->authSecret = $config['auth_secret'];
    }

    private function http()
    {
        return Http::withHeaders([
            'Gateway-Auth-Token'  => $this->authToken,
            'Gateway-Auth-Secret' => $this->authSecret,
        ]);
    }

    public function listTransactions(): array
    {
        $response = $this->http()->get("{$this->baseUrl}/transacoes");

        if ($response->failed()) {
            throw new RuntimeException('Erro ao listar transações no Gateway 2');
        }

        return $response->json();
    }

    public function createTransaction(TransactionData $data): array
    {
        $payload = [
            'valor'        => $data->amount,
            'nome'         => $data->name,
            'email'        => $data->email,
            'numeroCartao' => $data->cardNumber,
            'cvv'          => $data->cvv,
        ];

        $response = $this->http()->post("{$this->baseUrl}/transacoes", $payload);

        if ($response->failed()) {
            throw new RuntimeException('Erro ao criar transação no Gateway 2');
        }

        return $response->json();
    }

    public function refund(string $transactionId): array
    {
        $payload = ['id' => $transactionId];

        $response = $this->http()->post("{$this->baseUrl}/transacoes/reembolso", $payload);

        if ($response->failed()) {
            throw new RuntimeException('Erro ao reembolsar transação no Gateway 2');
        }

        return $response->json();
    }
}
