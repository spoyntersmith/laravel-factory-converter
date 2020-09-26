<?php

use App\Models\User;
use Tests\TestCase;

class ExampleClass extends TestCase
{
    public function exampleTest()
    {
        $userOne = User::factory()->create(['email' => 'user@example.com']);
        $userTwo = User::factory()->make(['email' => 'user@example.org']);

        $this->assertNotNull($userOne);
    }
}
