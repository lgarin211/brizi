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
            $table->string('order_id')->nullable()->after('id')->index();
            $table->string('payment_type')->nullable()->after('price');
            $table->text('midtrans_response')->nullable()->after('transactionpoin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaction', function (Blueprint $table) {
            $table->dropColumn(['order_id', 'payment_type', 'midtrans_response']);
        });
    }
};
