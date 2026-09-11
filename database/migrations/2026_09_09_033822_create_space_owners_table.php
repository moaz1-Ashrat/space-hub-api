<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('space_owners', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('tax_registration_number', 9)->unique();
            $table->timestamps();

            $table->foreign('id')
                ->references('id')
                ->on('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('space_owners');
    }
};
