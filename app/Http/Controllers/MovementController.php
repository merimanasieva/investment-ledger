<?php

namespace App\Http\Controllers;

use App\Enums\MovementType;
use App\Http\Requests\StoreMovementRequest;
use App\Models\Client;
use App\Services\MovementService;
use Illuminate\Http\JsonResponse;

class MovementController extends Controller
{
    public function __construct(
        private MovementService $movementService
    ) {
    }

    public function store(
        StoreMovementRequest $request,
        Client $client
    ): JsonResponse {
        $account = $client->account;

        $movement = $this->movementService->createMovement(
            $account,
            MovementType::from($request->input('type')),
            $request->input('amount'),
            $request->input('instrument'),
            $request->input('quantity'),
            $request->input('price')
        );

        return response()->json([
            'message' => 'Movement created successfully.',
            'movement' => $movement,
            'balance' => $this->movementService->getBalance($account->fresh('movements')),
        ], 201);
    }
}
