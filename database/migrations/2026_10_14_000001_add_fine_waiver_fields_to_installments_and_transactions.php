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
        Schema::table('installments', function (Blueprint $table) {
            $table->decimal('fine_waived_amount', 12, 2)->default(0);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('fine_waived_amount', 12, 2)->default(0);
            $table->unsignedBigInteger('fine_waived_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('installments', function (Blueprint $table) {
            $table->dropColumn('fine_waived_amount');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['fine_waived_amount', 'fine_waived_by']);
        });
    }
};
