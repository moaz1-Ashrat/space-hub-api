<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('space_id');

            $table->integer('rating');
            $table->text('comment')->nullable();
            $table->date('review_date');
            $table->timestamps();

            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('space_id')
                ->references('id')
                ->on('spaces')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->index('customer_id');
            $table->index('space_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
