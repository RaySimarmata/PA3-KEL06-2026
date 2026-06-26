<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ResetAdminPasswordSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        
        if ($admin) {
            $admin->password = Hash::make('password');
            $admin->save();
            
            $this->command->info('Password admin berhasil direset ke: password');
        } else {
            $this->command->error('User admin tidak ditemukan');
        }
    }
}
