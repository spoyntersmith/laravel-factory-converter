<?php

use Faker\Generator as Faker;

$factory->define(App\Models\Client::class, function (Faker $faker) {
    return [
        'company'  => $faker->company,
        'email'    => $faker->safeEmail,
        'address'  => $faker->address,
        'postcode' => $faker->postcode,
        'city'     => $faker->city,
    ];
});
