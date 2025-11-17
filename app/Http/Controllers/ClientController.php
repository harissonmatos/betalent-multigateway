<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\JsonResponse;

class ClientController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Client::paginate(20));
    }


    public function show(Client $client)
    {
        $client->load([
            'transactions.gateway:id,name',
            'transactions.products:id,quantity,product_id,transaction_id',
            'transactions.products.product:id,name,amount',
        ]);

        return response()->json($client);
    }
}
