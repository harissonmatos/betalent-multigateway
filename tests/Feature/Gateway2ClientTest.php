<?php

namespace Tests\Feature;

use App\DTOs\Payment\TransactionData;
use App\Services\Payment\Gateway2Client;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class Gateway2ClientTest extends TestCase
{
    public function test_gateway2_lists_transactions()
    {
        Http::fake([
            'http://gateway2:3002/transacoes' => Http::response([
                ['id' => 10, 'valor' => 1000]
            ], 200),
        ]);

        $client = new Gateway2Client();

        $transactions = $client->listTransactions();

        $this->assertIsArray($transactions);
        $this->assertEquals(10, $transactions[0]['id']);
    }

    public function test_gateway2_creates_transaction()
    {
        Http::fake([
            'http://gateway2:3002/transacoes' => Http::response([
                'status' => 'ok',
                'id' => 777
            ], 200),
        ]);

        $client = new Gateway2Client();

        $data = new TransactionData(
            1000,
            'Joao',
            'joao@test.com',
            '5569000000006063',
            '010'
        );

        $result = $client->createTransaction($data);

        $this->assertEquals(777, $result['id']);
    }

    public function test_gateway2_refunds_transaction()
    {
        Http::fake([
            'http://gateway2:3002/transacoes/reembolso' => Http::response([
                'status' => 'refund_ok'
            ], 200),
        ]);

        $client = new Gateway2Client();

        $result = $client->refund('123');

        $this->assertEquals('refund_ok', $result['status']);
    }
}
