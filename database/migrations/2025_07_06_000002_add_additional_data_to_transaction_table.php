<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */    public function up(): void
    {
        Schema::table('transaction', function (Blueprint $table) {
            // Add additonaldata column to store detail as JSON
            $table->json('additonaldata')->nullable()->after('midtrans_response');
            
            // Add index for better performance on payment_type queries if not exists
            if (!Schema::hasIndex('transaction', 'transaction_payment_type_index')) {
                $table->index('payment_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */    public function down(): void
    {
        Schema::table('transaction', function (Blueprint $table) {
            $table->dropColumn('additonaldata');
            
            if (Schema::hasIndex('transaction', 'transaction_payment_type_index')) {
                $table->dropIndex(['payment_type']);
            }
        });
    }
};
