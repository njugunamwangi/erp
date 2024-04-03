<?php

use App\InvoiceSeries;
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
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('series')->default(InvoiceSeries::IN2INV->name);
            $table->integer('serial_number')->nullable();
            $table->string('serial')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('series');
            $table->dropColumn('serial_number');
            $table->dropColumn('serial');
        });
    }
};
