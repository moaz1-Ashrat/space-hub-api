<?php
try {
    \Illuminate\Support\Facades\Mail::raw(
        "Test at " . now(),
        fn ($m) => $m->to('test@spacehub.test')->subject('Space Hub Test')
    );
    echo "\n? Email sent! Check mailtrap.io\n";
} catch (\Throwable $e) {
    echo "\n? ERROR: " . $e->getMessage() . "\n";
}
