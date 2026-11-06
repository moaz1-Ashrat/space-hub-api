<?php
$u = App\Models\User::where('email', 'admin@spacehub.test')->with('admin')->first();
if ($u) {
    echo "Name:     " . $u->name . PHP_EOL;
    echo "Email:    " . $u->email . PHP_EOL;
    echo "Role:     " . $u->role . PHP_EOL;
    echo "Active:   " . ($u->is_active ? 'yes' : 'no') . PHP_EOL;
    echo "Level:    " . ($u->admin->level_of_authority ?? 'N/A') . PHP_EOL;
    echo "Password check: " . (password_verify('root', $u->password) ? '? OK' : '? FAIL') . PHP_EOL;
} else {
    echo "? Not found!" . PHP_EOL;
}
