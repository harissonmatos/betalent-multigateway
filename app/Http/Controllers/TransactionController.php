<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\Payment\PaymentGatewayManager;
use Illuminate\Http\JsonResponse;

class TransactionController extends Controller
{
    public function __construct(
            private PaymentGatewayManager $gateways
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json(
                Transaction::with('client', 'gateway')->paginate(20)
        );
    }


    public function show(Transaction $transaction)
    {
        $transaction->load([
                'gateway:id,name,slug',
                'products:id,quantity,product_id,transaction_id',
                'products.product:id,name,amount',
        ]);

        return response()->json($transaction);
    }


    public function refund(Transaction $transaction)
    {
        if ($transaction->status !== 'paid') {
            return response()->json([
                    'message' => 'Esta transação não pode ser reembolsada.'
            ], 422);
        }

        $gateway = $transaction->gateway;

        if (!$gateway || !$gateway->is_active) {
            return response()->json([
                    'message' => 'Gateway indisponível para reembolso.'
            ], 422);
        }

        $client = $this->gateways->driver($gateway->slug);

        try {
            $client->refund($transaction->id);
        } catch (\Throwable $e) {
            return response()->json([
                    'message' => 'Falha ao processar reembolso com o gateway.'
            ], 500);
        }

        $transaction->update([
                'status' => 'refunded',
        ]);

        return response()->json([
                'message' => 'Reembolso realizado com sucesso.',
                'transaction' => $transaction->fresh(),
        ]);
    }
}
