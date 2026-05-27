<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(PermissionSeeder::class);
        $this->call(MenuSeeder::class);
        $this->call(VehicleBrandSeeder::class);

        $admin = User::query()->updateOrCreate([
            'phone' => '09126860148',
        ], [
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => '123456789',
        ]);

        $admin->syncRoles(['admin']);
    }
}
