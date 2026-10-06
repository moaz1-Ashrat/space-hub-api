<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('space_images', function (Blueprint $table) {
            $table->id();

            $table->foreignId('space_id')
                ->constrained('spaces')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('image_path');
            $table->integer('order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index('space_id');
            $table->index('is_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('space_images');
    }
};