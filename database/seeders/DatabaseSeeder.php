<?php

namespace Database\Seeders;

use App\Enums\MovementType;
use App\Models\Client;
use App\Models\Movement;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $ana = Client::create([
            'name' => 'Ana',
        ]);

        $anaAccount = $ana->account()->create([
            'currency' => 'EUR',
        ]);

        Movement::create([
            'account_id' => $anaAccount->id,
            'type' => MovementType::DEPOSIT,
            'amount' => 1000,
        ]);

        Movement::create([
            'account_id' => $anaAccount->id,
            'type' => MovementType::BUY,
            'amount' => 500,
            'instrument' => 'AAPL',
            'quantity' => 5,
            'price' => 100,
        ]);

        Movement::create([
            'account_id' => $anaAccount->id,
            'type' => MovementType::SELL,
            'amount' => 360,
            'instrument' => 'AAPL',
            'quantity' => 3,
            'price' => 120,
        ]);

        $marko = Client::create([
            'name' => 'Marko',
        ]);

        $markoAccount = $marko->account()->create([
            'currency' => 'USD',
        ]);

        Movement::create([
            'account_id' => $markoAccount->id,
            'type' => MovementType::DEPOSIT,
            'amount' => 2000,
        ]);

        Movement::create([
            'account_id' => $markoAccount->id,
            'type' => MovementType::BUY,
            'amount' => 1500,
            'instrument' => 'MSFT',
            'quantity' => 10,
            'price' => 150,
        ]);
    }
}
