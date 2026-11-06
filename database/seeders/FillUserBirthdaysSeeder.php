<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class FillUserBirthdaysSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::whereNull('date_of_birth')->get();
        $count = 0;

        foreach ($users as $user) {
            $user->date_of_birth = fake()
                ->dateTimeBetween('-65 years', '-18 years')
                ->format('Y-m-d');
            $user->save();
            $count++;
        }

        $this->command->info("Filled DOB for {$count} users.");
    }
}