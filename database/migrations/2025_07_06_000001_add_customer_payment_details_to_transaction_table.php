<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transaction', function (Blueprint $table) {
            // Add customer details column
            $table->json('customer_details')->nullable()->after('midtrans_response');

            // Add payment method specific details column
            $table->json('payment_details')->nullable()->after('customer_details');

            // Add index for better performance on payment_type queries
            $table->index('payment_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaction', function (Blueprint $table) {
            $table->dropIndex(['payment_type']);
            $table->dropColumn(['customer_details', 'payment_details']);
        });
    }
};
