<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('commission_rate', 5, 4)->nullable()->after('total_amount');
            $table->decimal('commission_amount', 10, 2)->nullable()->after('commission_rate');
            $table->decimal('customer_paid', 10, 2)->nullable()->after('commission_amount');
            $table->decimal('owner_payout', 10, 2)->nullable()->after('customer_paid');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'commission_rate',
                'commission_amount',
                'customer_paid',
                'owner_payout',
            ]);
        });
    }
};
