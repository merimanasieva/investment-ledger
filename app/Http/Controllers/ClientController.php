<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\MovementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:clients,name'],
            'currency' => ['required', 'string', 'size:3'],
        ]);

        $client = Client::create([
            'name' => $validated['name'],
        ]);

        $client->account()->create([
            'currency' => strtoupper($validated['currency']),
        ]);

        return response()->json(
            $client->load('account'),
            201
        );
    }

    public function show(Client $client): JsonResponse
    {
        return response()->json(
            $client->load('account')
        );
    }

    public function balance(
        Client $client,
        MovementService $movementService
    ): JsonResponse {
        $account = $client->account->load('movements');

        return response()->json([
            'client' => $client->name,
            'currency' => $account->currency,
            ...$movementService->getBalance($account),
        ]);
    }

    public function movements(Client $client): JsonResponse
    {
        $movements = $client->account
            ->movements()
            ->orderBy('id')
            ->get();

        return response()->json($movements);
    }
}
