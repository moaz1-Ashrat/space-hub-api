<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->decimal('discount_value', 10, 2)->default(0.00);
            $table->decimal('commission_rate', 5, 4)->nullable();
            $table->boolean('is_used')->default(false);
            $table->timestamp('expiry_date')->nullable();
            $table->timestamp('used_at')->nullable();

            // nullable لأن الكوبون يُنشأ قبل الحجز
            $table->foreignId('booking_id')
                ->nullable()
                ->constrained('bookings')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
