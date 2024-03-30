<?php

use App\InvoiceStatus;
use App\Models\Quote;
use App\Models\User;
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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class);
            $table->foreignIdFor(Quote::class)->nullable();
            $table->json('items');
            $table->bigInteger('subtotal');
            $table->bigInteger('taxes');
            $table->bigInteger('total');
            $table->string('status')->default(InvoiceStatus::Unpaid);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
