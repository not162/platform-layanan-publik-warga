<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        // User::factory(10)->create();

        $this->call([
            PengurusSeeder::class,
            LetterSeeder::class,
            PortalContentSeeder::class,
        ]);
    }
}
