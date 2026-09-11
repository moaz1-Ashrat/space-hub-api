<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('space_id')
                ->constrained('spaces')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('payment_id')
                ->constrained('payments')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->date('booking_date');
            $table->time('start_time');
            $table->time('end_time');

            $table->dateTime('created_at');

            $table->decimal('total_amount', 10, 2);
            $table->enum('booking_status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');
            $table->string('attendance_status', 30);
            $table->boolean('historical_booking')->default(false);

            $table->softDeletes();
            $table->timestamp('updated_at')->nullable();

            $table->index('user_id');
            $table->index('space_id');
            $table->index('payment_id');
            $table->index('booking_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
