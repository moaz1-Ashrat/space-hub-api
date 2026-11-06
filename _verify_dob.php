<?php
$total = App\Models\User::count();
$withDob = App\Models\User::whereNotNull('date_of_birth')->count();
$avgAge = round(
    App\Models\User::whereNotNull('date_of_birth')
        ->selectRaw('AVG(TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE())) as a')
        ->value('a'),
    1
);
echo "Total users: " . $total . PHP_EOL;
echo "With DOB:    " . $withDob . PHP_EOL;
echo "Average age: " . $avgAge . PHP_EOL;
