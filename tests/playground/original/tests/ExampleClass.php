<?php

use App\Models\User;
use Tests\TestCase;

class ExampleClass extends TestCase
{
    public function exampleTest()
    {
        $userOne = factory(User::class)->create(['email' => 'user@example.com']);
        $userTwo = factory(User::class)->make(['email' => 'user@example.org']);

        $this->assertNotNull($userOne);
    }
}
