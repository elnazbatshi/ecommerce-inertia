<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the default admin user.
     */
    public function run(): void
    {
        $this->call(PermissionSeeder::class);

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
