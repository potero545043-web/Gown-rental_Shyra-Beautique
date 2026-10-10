<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('damage_reports', function (Blueprint $table) {
            $table->foreignId('reservation_id')->nullable()->constrained()->nullOnDelete();
            $table->date('discovered_at')->nullable();
            $table->decimal('estimated_repair_cost', 10, 2)->nullable();
            $table->decimal('final_repair_cost', 10, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('damage_reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reservation_id');
            $table->dropColumn([
                'discovered_at',
                'estimated_repair_cost',
                'final_repair_cost',
            ]);
        });
    }
};
