<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Gateway;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'client_id'        => Client::factory(),
            'gateway_id'       => Gateway::class,
            'external_id'      => $this->faker->uuid(),
            'status'           => $this->faker->randomElement(['paid', 'failed', 'pending']),
            'amount'           => $this->faker->randomFloat(2, 10, 2000),
            'card_last_numbers'=> strval($this->faker->numberBetween(1000, 9999)),
        ];
    }
}
