<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('name', 100);
            $table->string('location', 255);
            $table->text('description')->nullable();
            $table->string('space_size', 50);
            $table->integer('capacity_people');
            $table->decimal('price_per_hour', 10, 2);
            $table->string('space_type', 50);
            $table->string('device_type', 100)->nullable();
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->softDeletes();
            $table->timestamps();

            $table->index('approval_status');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spaces');
    }
};
