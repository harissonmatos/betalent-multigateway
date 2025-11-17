<?php

namespace App\Http\Controllers;

use App\Models\Gateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GatewayController extends Controller
{
    /**
     * Listar gateways ordenados pela prioridade
     */
    public function index(): JsonResponse
    {
        return response()->json(Gateway::orderBy('priority')->get());
    }

    /**
     * Ativar gateway
     */
    public function activate(Gateway $gateway): JsonResponse
    {
        $gateway->update(['is_active' => true]);

        return response()->json([
            'message' => 'Gateway ativado',
            'gateway' => $gateway
        ]);
    }

    /**
     * Desativar gateway
     */
    public function deactivate(Gateway $gateway): JsonResponse
    {
        $gateway->update(['is_active' => false]);

        return response()->json([
            'message' => 'Gateway desativado',
            'gateway' => $gateway
        ]);
    }

    /**
     * Atualizar prioridade do gateway
     */
    public function updatePriority(Request $request, Gateway $gateway): JsonResponse
    {
        $request->validate([
            'priority' => 'required|integer|min:1',
        ]);

        $newPriority = $request->priority;

        // 🔥 1️⃣ Pega lista ordenada REAL e remove o gateway atual
        $gateways = Gateway::orderBy('priority')->get()
            ->reject(fn($g) => $g->id === $gateway->id)
            ->values(); // reindexa

        // 🔥 2️⃣ Insere o gateway na nova posição
        $position = max(0, min($newPriority - 1, $gateways->count()));
        $gateways->splice($position, 0, [$gateway]);

        // 🔥 3️⃣ Atualiza prioridades sequencialmente (1,2,3...)
        foreach ($gateways as $index => $g) {
            $g->priority = $index + 1;
            $g->save();
        }

        return response()->json([
            'message' => 'Prioridade atualizada',
            'gateway' => $gateway->fresh(),
        ]);
    }
}
