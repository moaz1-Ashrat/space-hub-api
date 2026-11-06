<?php
$tables = ['users','customers','space_owners','admins','spaces','space_images','availabilities','coupons','bookings','payments','reviews'];
foreach ($tables as $t) {
    echo str_pad($t, 20) . ': ' . DB::table($t)->count() . PHP_EOL;
}
