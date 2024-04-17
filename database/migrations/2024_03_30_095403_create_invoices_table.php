<?php

use App\Enums\InvoiceStatus;
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
            $table->foreignIdFor(User::class)->cascadeOnDelete();
            $table->foreignIdFor(Quote::class)->nullable()->cascadeOnDelete();
            $table->json('items');
            $table->bigInteger('subtotal');
            $table->bigInteger('taxes');
            $table->bigInteger('total');
            $table->enum('status', InvoiceStatus::values())->default(InvoiceStatus::Unpaid);
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
