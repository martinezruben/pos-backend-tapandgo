<?php

namespace Database\Factories;

use App\Models\TransactionPayment;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionPaymentFactory extends Factory
{
    protected $model = TransactionPayment::class;

    public function definition(): array
    {
        return [
            'transaction_id' => Transaction::factory(),
            'payment_method' => $this->faker->randomElement(['Cash', 'Credit Card', 'Debit Card', 'Check']),
            'amount' => $this->faker->randomFloat(2, 10, 500),
            'reference' => $this->faker->optional()->word(),
        ];
    }
}
