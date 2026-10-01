<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('reservations', 'agreement_accepted_ip')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->string('agreement_accepted_ip', 45)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('reservations', 'agreement_accepted_ip')) {
            DB::table('reservations')->whereNotNull('agreement_accepted_ip')->update(['agreement_accepted_ip' => null]);
            Schema::table('reservations', function (Blueprint $table) {
                $table->timestamp('agreement_accepted_ip')->nullable()->change();
            });
        }
    }
};