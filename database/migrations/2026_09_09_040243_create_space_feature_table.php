<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('space_feature', function (Blueprint $table) {
            $table->unsignedBigInteger('space_id');
            $table->unsignedBigInteger('feature_id');

            $table->primary(['space_id', 'feature_id']);

            $table->foreign('space_id')
                ->references('id')
                ->on('spaces')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('feature_id')
                ->references('id')
                ->on('features')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->index('space_id');
            $table->index('feature_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('space_feature');
    }
};
