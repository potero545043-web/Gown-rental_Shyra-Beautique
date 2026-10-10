<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gown_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gown_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gown_id')->constrained()->restrictOnDelete();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->timestamp('purchased_at');
            $table->timestamps();

            $table->unique(['reservation_id', 'gown_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gown_purchases');
    }
};
