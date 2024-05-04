<?php

use App\Enums\QuoteSeries;
use App\Models\Task;
use App\Models\User;
use App\Models\Vertical;
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
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->boolean('task');
            $table->foreignIdFor(Task::class)->nullable()->cascadeOnDelete();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Vertical::class)->constrained()->cascadeOnDelete();
            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('taxes');
            $table->unsignedInteger('total');
            $table->enum('series', QuoteSeries::values())->default(QuoteSeries::IN2QUT->name);
            $table->integer('serial_number')->nullable();
            $table->string('serial')->nullable();
            $table->json('items');
            $table->longText('notes');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
