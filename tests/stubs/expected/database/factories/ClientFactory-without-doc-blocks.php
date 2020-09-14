<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition()
    {
        return [
            'company'  => $this->faker->company,
            'email'    => $this->faker->safeEmail,
            'address'  => $this->faker->address,
            'postcode' => $this->faker->postcode,
            'city'     => $this->faker->city,
        ];
    }
}
