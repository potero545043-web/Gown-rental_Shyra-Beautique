<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A gown can also be sold directly (walk-in sale) without a linked rental
 * return, so the reservation / return references must be optional.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('gown_purchases', function (Blueprint $table) {
            $table->foreignId('reservation_id')->nullable()->change();
            $table->foreignId('gown_return_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('gown_purchases', function (Blueprint $table) {
            $table->foreignId('reservation_id')->nullable(false)->change();
            $table->foreignId('gown_return_id')->nullable(false)->change();
        });
    }
};
