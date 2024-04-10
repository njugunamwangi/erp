<?php

use App\Enums\QuoteSeries;
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
        Schema::table('quotes', function (Blueprint $table) {
            $table->string('series')->default(QuoteSeries::IN2QUT->name);
            $table->integer('serial_number')->nullable();
            $table->string('serial')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn('series');
            $table->dropColumn('serial_number');
            $table->dropColumn('serial');
        });
    }
};
