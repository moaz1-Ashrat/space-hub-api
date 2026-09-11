<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained('spaces')->cascadeOnUpdate()->restrictOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 0-6
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_available')->default(true);
            $table->date('special_date')->nullable();
            $table->timestamps();

            $table->index('space_id');
            $table->index('day_of_week');
            $table->index('special_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('availabilities');
    }
};
