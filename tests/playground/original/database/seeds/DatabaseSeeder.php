<?php

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        factory(Post::class, 10)->create();

        factory(User::class)->create([
            'email' => 'some@example.com',
            'password' => bcrypt('secret'),
        ]);
    }
}
