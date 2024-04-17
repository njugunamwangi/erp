<?php

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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class, 'assigned_by')->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'assigned_to')->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'assigned_for')->cascadeOnDelete();
            $table->foreignIdFor(Vertical::class)->cascadeOnDelete();
            $table->text('description');
            $table->date('due_date')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
