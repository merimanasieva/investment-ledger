<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Models\Account;
use App\Models\Movement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MovementService
{
    public function createMovement(
        Account $account,
        MovementType $type,
        ?float $amount = null,
        ?string $instrument = null,
        ?int $quantity = null,
        ?float $price = null
    ): Movement {
        return DB::transaction(function () use (
            $account,
            $type,
            $amount,
            $instrument,
            $quantity,
            $price
        ) {
            $cash = $this->getCashBalance($account);

            if ($type === MovementType::DEPOSIT) {
                return Movement::create([
                    'account_id' => $account->id,
                    'type' => $type,
                    'amount' => $amount,
                ]);
            }

            if ($type === MovementType::WITHDRAWAL) {
                if ($amount > $cash) {
                    throw ValidationException::withMessages([
                        'amount' => 'Insufficient cash balance.',
                    ]);
                }

                return Movement::create([
                    'account_id' => $account->id,
                    'type' => $type,
                    'amount' => $amount,
                ]);
            }

            $total = $quantity * $price;

            if ($type === MovementType::BUY) {
                if ($total > $cash) {
                    throw ValidationException::withMessages([
                        'amount' => 'Insufficient cash balance for this purchase.',
                    ]);
                }

                return Movement::create([
                    'account_id' => $account->id,
                    'type' => $type,
                    'amount' => $total,
                    'instrument' => $instrument,
                    'quantity' => $quantity,
                    'price' => $price,
                ]);
            }

            if ($type === MovementType::SELL) {
                $holdings = $this->getHolding($account, $instrument);

                if ($quantity > $holdings) {
                    throw ValidationException::withMessages([
                        'quantity' => 'Insufficient holdings for this sale.',
                    ]);
                }

                return Movement::create([
                    'account_id' => $account->id,
                    'type' => $type,
                    'amount' => $total,
                    'instrument' => $instrument,
                    'quantity' => $quantity,
                    'price' => $price,
                ]);
            }

            throw ValidationException::withMessages([
                'type' => 'Invalid movement type.',
            ]);
        });
    }

    public function getCashBalance(Account $account): float
    {
        $balance = 0;

        foreach ($account->movements as $movement) {
            if ($movement->type === MovementType::DEPOSIT) {
                $balance += (float) $movement->amount;
            }

            if ($movement->type === MovementType::WITHDRAWAL) {
                $balance -= (float) $movement->amount;
            }

            if ($movement->type === MovementType::BUY) {
                $balance -= (float) $movement->amount;
            }

            if ($movement->type === MovementType::SELL) {
                $balance += (float) $movement->amount;
            }
        }

        return $balance;
    }

    public function getHolding(Account $account, string $instrument): int
    {
        $holding = 0;

        foreach ($account->movements as $movement) {
            if ($movement->instrument !== $instrument) {
                continue;
            }

            if ($movement->type === MovementType::BUY) {
                $holding += $movement->quantity;
            }

            if ($movement->type === MovementType::SELL) {
                $holding -= $movement->quantity;
            }
        }

        return $holding;
    }

    public function getBalance(Account $account): array
    {
        $cash = $this->getCashBalance($account);

        $holdings = [];

        foreach ($account->movements as $movement) {
            if (!$movement->instrument) {
                continue;
            }

            $holdings[$movement->instrument] = $this->getHolding(
                $account,
                $movement->instrument
            );
        }

        $holdings = array_filter(
            $holdings,
            fn ($quantity) => $quantity > 0
        );

        return [
            'cash' => number_format($cash, 2, '.', ''),
            'holdings' => $holdings,
        ];
    }
}
