<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\Client;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionProduct;
use App\Services\Payment\PaymentGatewayManager;
use App\DTOs\Payment\TransactionData;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function __construct(
        private PaymentGatewayManager $gateways
    ) {
    }

    /**
     * @throws \Throwable
     */
    public function store(CheckoutRequest $request)
    {
        $validated = $request->validated();

        return DB::transaction(function () use ($validated) {
            $client = Client::firstOrCreate(
                ['email' => $validated['client']['email']],
                ['name' => $validated['client']['name']]
            );

            $totalAmount = $this->calculateTotal($validated['products']);

            $transaction = Transaction::create([
                'client_id' => $client->id,
                'amount' => $totalAmount,
                'status' => 'pending',
                'card_last_numbers' => substr($validated['payment']['cardNumber'], -4),
            ]);

            foreach ($validated['products'] as $item) {
                TransactionProduct::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $item['id'],
                    'quantity' => $item['quantity'],
                ]);
            }

            $paymentResult = $this->performPayment(
                $validated,
                $transaction
            );

            $transaction->update([
                'gateway_id'  => $paymentResult['gateway_id'],
                'external_id' => $paymentResult['external_id'] ?? null,
                'status'      => $paymentResult['status'],
            ]);

            return response()->json([
                'success'     => true,
                'transaction' => $transaction->fresh(),
            ], 201);
        });
    }

    private function calculateTotal(array $items): float
    {
        $total = 0;

        foreach ($items as $item) {
            $product = Product::find($item['id']);
            $total += $product->amount * $item['quantity'];
        }

        return round($total, 2);
    }

    private function performPayment(array $validated, Transaction $transaction): array
    {
        $data = new TransactionData(
            amount: (int) ($transaction->amount * 100), // converter para centavos
            name: $validated['client']['name'],
            email: $validated['client']['email'],
            cardNumber: $validated['payment']['cardNumber'],
            cvv: $validated['payment']['cvv'],
        );

        $gateways = \App\Models\Gateway::where('is_active', true)
            ->orderBy('priority')
            ->get();

        foreach ($gateways as $gateway) {
            try {
                $client = $this->gateways->driver($gateway->slug);

                $response = $client->createTransaction($data);

                return [
                    'gateway_id' => $gateway->id,
                    'external_id' => $response['id'] ?? null,
                    'status' => 'paid',
                ];
            } catch (\Throwable $e) {
                // tenta o próximo gateway
                continue;
            }
        }

        // nenhum gateway funcionou
        return [
            'gateway_id' => null,
            'external_id' => null,
            'status' => 'failed',
        ];
    }
}
