<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Database\Seeders\PengurusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_database_seeder_does_not_create_demo_accounts(): void
    {
        $this->app['env'] = 'production';

        $this->app->make(DatabaseSeeder::class)->run();

        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
        $this->assertDatabaseMissing('users', ['email' => 'ketua_rt@warga.local']);
        $this->assertDatabaseMissing('users', ['email' => 'superadmin@warga.local']);
    }

    public function test_production_pengurus_seeder_does_not_create_default_accounts(): void
    {
        $this->app['env'] = 'production';

        $this->app->make(PengurusSeeder::class)->run();

        $this->assertDatabaseMissing('users', ['email' => 'ketua_rt@warga.local']);
        $this->assertDatabaseMissing('users', ['email' => 'superadmin@warga.local']);
    }

    public function test_testing_pengurus_seeder_creates_distinct_officer_roles_and_superadmin(): void
    {
        $this->app['env'] = 'testing';

        $this->app->make(PengurusSeeder::class)->run();

        $this->assertDatabaseHas('users', ['email' => 'superadmin@warga.local', 'role' => 'SUPERADMIN']);
        $this->assertDatabaseHas('users', ['email' => 'ketua_rt@warga.local', 'role' => 'KETUA_RT']);
        $this->assertDatabaseHas('users', ['email' => 'sekretaris@warga.local', 'role' => 'SEKRETARIS']);
        $this->assertDatabaseHas('users', ['email' => 'bendahara@warga.local', 'role' => 'BENDAHARA']);
        $this->assertDatabaseHas('users', ['email' => 'keamanan@warga.local', 'role' => 'PETUGAS_KEAMANAN']);
    }
}
