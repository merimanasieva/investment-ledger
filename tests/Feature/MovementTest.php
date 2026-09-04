<?php

namespace Tests\Feature;

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MovementTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_be_created(): void
    {
        $response = $this->postJson('/api/clients', [
            'name' => 'Ana',
            'currency' => 'EUR',
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('name', 'Ana');
    }

    public function test_deposit_increases_cash_balance(): void
    {
        $client = Client::factory()->create([
            'name' => 'Ana',
        ]);

        $client->account()->create([
            'currency' => 'EUR',
        ]);

        $response = $this->postJson(
            "/api/clients/{$client->id}/movements",
            [
                'type' => 'deposit',
                'amount' => 1000,
            ]
        );

        $response
            ->assertStatus(201)
            ->assertJsonPath('balance.cash', '1000.00');
    }

    public function test_withdrawal_reduces_cash_balance(): void
    {
        $client = Client::factory()->create([
            'name' => 'Ana',
        ]);

        $client->account()->create([
            'currency' => 'EUR',
        ]);

        $this->postJson(
            "/api/clients/{$client->id}/movements",
            [
                'type' => 'deposit',
                'amount' => 1000,
            ]
        );

        $response = $this->postJson(
            "/api/clients/{$client->id}/movements",
            [
                'type' => 'withdrawal',
                'amount' => 300,
            ]
        );

        $response
            ->assertStatus(201)
            ->assertJsonPath('balance.cash', '700.00');
    }

    public function test_withdrawal_cannot_exceed_cash_balance(): void
    {
        $client = Client::factory()->create([
            'name' => 'Ana',
        ]);

        $client->account()->create([
            'currency' => 'EUR',
        ]);

        $this->postJson(
            "/api/clients/{$client->id}/movements",
            [
                'type' => 'deposit',
                'amount' => 500,
            ]
        );

        $response = $this->postJson(
            "/api/clients/{$client->id}/movements",
            [
                'type' => 'withdrawal',
                'amount' => 600,
            ]
        );

        $response->assertStatus(422);

        $this->getJson(
            "/api/clients/{$client->id}/balance"
        )
        ->assertJsonPath('cash', '500.00');
    }

    public function test_buy_reduces_cash_and_increases_holdings(): void
    {
        $client = Client::factory()->create([
            'name' => 'Ana',
        ]);

        $client->account()->create([
            'currency' => 'EUR',
        ]);

        $this->postJson(
            "/api/clients/{$client->id}/movements",
            [
                'type' => 'deposit',
                'amount' => 1000,
            ]
        );

        $response = $this->postJson(
            "/api/clients/{$client->id}/movements",
            [
                'type' => 'buy',
                'instrument' => 'AAPL',
                'quantity' => 5,
                'price' => 100,
            ]
        );

        $response
            ->assertStatus(201)
            ->assertJsonPath('balance.cash', '500.00')
            ->assertJsonPath('balance.holdings.AAPL', 5);
    }

    public function test_buy_cannot_exceed_cash_balance(): void
    {
        $client = Client::factory()->create([
            'name' => 'Ana',
        ]);

        $client->account()->create([
            'currency' => 'EUR',
        ]);

        $this->postJson(
            "/api/clients/{$client->id}/movements",
            [
                'type' => 'deposit',
                'amount' => 500,
            ]
        );

        $response = $this->postJson(
            "/api/clients/{$client->id}/movements",
            [
                'type' => 'buy',
                'instrument' => 'AAPL',
                'quantity' => 10,
                'price' => 100,
            ]
        );

        $response->assertStatus(422);

        $this->getJson(
            "/api/clients/{$client->id}/balance"
        )
        ->assertJsonPath('cash', '500.00');
    }

    public function test_sell_increases_cash_and_reduces_holdings(): void
    {
        $client = Client::factory()->create([
            'name' => 'Ana',
        ]);

        $client->account()->create([
            'currency' => 'EUR',
        ]);

        $this->postJson(
            "/api/clients/{$client->id}/movements",
            [
                'type' => 'deposit',
                'amount' => 1000,
            ]
        );

        $this->postJson(
            "/api/clients/{$client->id}/movements",
            [
                'type' => 'buy',
                'instrument' => 'AAPL',
                'quantity' => 5,
                'price' => 100,
            ]
        );

        $response = $this->postJson(
            "/api/clients/{$client->id}/movements",
            [
                'type' => 'sell',
                'instrument' => 'AAPL',
                'quantity' => 3,
                'price' => 120,
            ]
        );

        $response
            ->assertStatus(201)
            ->assertJsonPath('balance.cash', '860.00')
            ->assertJsonPath('balance.holdings.AAPL', 2);
    }

    public function test_sell_cannot_exceed_holdings(): void
    {
        $client = Client::factory()->create([
            'name' => 'Ana',
        ]);

        $client->account()->create([
            'currency' => 'EUR',
        ]);

        $this->postJson(
            "/api/clients/{$client->id}/movements",
            [
                'type' => 'deposit',
                'amount' => 1000,
            ]
        );

        $this->postJson(
            "/api/clients/{$client->id}/movements",
            [
                'type' => 'buy',
                'instrument' => 'AAPL',
                'quantity' => 5,
                'price' => 100,
            ]
        );

        $response = $this->postJson(
            "/api/clients/{$client->id}/movements",
            [
                'type' => 'sell',
                'instrument' => 'AAPL',
                'quantity' => 8,
                'price' => 120,
            ]
        );

        $response->assertStatus(422);

        $this->getJson(
            "/api/clients/{$client->id}/balance"
        )
        ->assertJsonPath('cash', '500.00')
        ->assertJsonPath('holdings.AAPL', 5);
    }

    public function test_zero_or_negative_values_are_rejected(): void
    {
        $client = Client::factory()->create([
            'name' => 'Ana',
        ]);

        $client->account()->create([
            'currency' => 'EUR',
        ]);

        $response = $this->postJson(
            "/api/clients/{$client->id}/movements",
            [
                'type' => 'deposit',
                'amount' => 0,
            ]
        );

        $response->assertStatus(422);
    }
}
