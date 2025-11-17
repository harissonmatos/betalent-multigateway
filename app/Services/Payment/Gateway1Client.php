<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\DTOs\Payment\TransactionData;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class Gateway1Client implements PaymentGatewayInterface
{
    private string $baseUrl;
    private string $email;
    private string $token;
    private ?string $bearerToken = null;

    public function __construct()
    {
        $config = config('gateways.gateway1');

        $this->baseUrl = rtrim($config['base_url'], '/');
        $this->email = $config['email'];
        $this->token = $config['token'];
    }

    /**
     * @throws ConnectionException
     */
    public function listTransactions(): array
    {
        $response = $this->http()->get("{$this->baseUrl}/transactions");

        if ($response->failed()) {
            throw new RuntimeException('Erro ao listar transações no Gateway 1');
        }

        return $response->json();
    }

    /**
     * @throws ConnectionException
     */
    public function createTransaction(TransactionData $data): array
    {
        $payload = [
            'amount' => $data->amount,
            'name' => $data->name,
            'email' => $data->email,
            'cardNumber' => $data->cardNumber,
            'cvv' => $data->cvv,
        ];

        $response = $this->http()->post("{$this->baseUrl}/transactions", $payload);

        if ($response->failed()) {
            throw new RuntimeException('Erro ao criar transação no Gateway 1');
        }

        return $response->json();
    }

    /**
     * @throws ConnectionException
     */
    public function refund(string $transactionId): array
    {
        $url = "{$this->baseUrl}/transactions/{$transactionId}/charge_back";

        $response = $this->http()->post($url);

        if ($response->failed()) {
            throw new RuntimeException('Erro ao fazer chargeback no Gateway 1');
        }

        return $response->json();
    }

    /**
     * Faz o login e armazena o bearer token em memória.
     * @throws ConnectionException
     */
    private function authenticate(): void
    {
        if ($this->bearerToken) {
            return;
        }

        $response = Http::post("{$this->baseUrl}/login", [
            'email' => $this->email,
            'token' => $this->token,
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Falha ao autenticar no Gateway 1');
        }

        // Ajuste a chave abaixo de acordo com a resposta real do mock
        $this->bearerToken = $response->json('token')
            ?? throw new RuntimeException('Token não encontrado na resposta do Gateway 1');
    }

    /**
     * @throws ConnectionException
     */
    private function http(): \Illuminate\Http\Client\PendingRequest|\Illuminate\Http\Client\Factory
    {
        $this->authenticate();

        return Http::withToken($this->bearerToken);
    }
}
