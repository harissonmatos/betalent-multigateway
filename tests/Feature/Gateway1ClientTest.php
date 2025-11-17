<?php

namespace Tests\Feature;

use App\DTOs\Payment\TransactionData;
use App\Services\Payment\Gateway1Client;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class Gateway1ClientTest extends TestCase
{
    public function test_gateway1_authenticates_before_calls()
    {
        Http::fake([
            'http://gateway1:3001/login' => Http::response([
                'token' => 'abc123'
            ], 200),

            'http://gateway1:3001/transactions' => Http::response([
                ['id' => 1, 'amount' => 1000]
            ], 200),
        ]);

        $client = new Gateway1Client();
        $transactions = $client->listTransactions();

        $this->assertIsArray($transactions);
        $this->assertEquals(1, $transactions[0]['id']);

        Http::assertSent(fn ($request) => $request->url() === 'http://gateway1:3001/login');
    }

    public function test_gateway1_creates_transaction()
    {
        Http::fake([
            'http://gateway1:3001/login' => Http::response(['token' => 'abc123'], 200),
            'http://gateway1:3001/transactions' => Http::response([
                'status' => 'success',
                'id' => 999
            ], 200),
        ]);

        $client = new Gateway1Client();

        $data = new TransactionData(
            amount: 1000,
            name: 'Tester',
            email: 'tester@test.com',
            cardNumber: '5569000000006063',
            cvv: '010'
        );

        $result = $client->createTransaction($data);

        $this->assertEquals(999, $result['id']);
    }

    public function test_gateway1_chargeback()
    {
        Http::fake([
            'http://gateway1:3001/login' => Http::response(['token' => 'abc123'], 200),
            'http://gateway1:3001/transactions/123/charge_back' => Http::response([
                'status' => 'chargeback_ok'
            ], 200),
        ]);

        $client = new Gateway1Client();

        $result = $client->refund('123');

        $this->assertEquals('chargeback_ok', $result['status']);
    }
}
