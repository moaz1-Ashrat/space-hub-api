<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // امسح أي admin بنفس الإيميل لو موجود
        $existing = User::where('email', 'admin@spacehub.test')->first();
        if ($existing) {
            Admin::where('id', $existing->id)->delete();
            $existing->delete();
        }

        // اعمل الـ super admin الجديد
        $user = User::create([
            'first_name' => 'Moaz',
            'last_name'  => 'Ashraf',
            'email'      => 'admin@spacehub.test',
            'password'   => 'root',          // hashed تلقائيًا
            'phone'      => '01012345678',
            'gender'     => 'male',
            'date_of_birth' => '1995-05-15',
            'role'       => 'admin',
            'is_active'  => true,
        ]);

        Admin::create([
            'id' => $user->id,
            'level_of_authority' => 'super_admin',
        ]);

        $this->command->info('✅ Super Admin created:');
        $this->command->line('   Name:     ' . $user->name);
        $this->command->line('   Email:    ' . $user->email);
        $this->command->line('   Password: root');
        $this->command->line('   Level:    super_admin');
    }
}
