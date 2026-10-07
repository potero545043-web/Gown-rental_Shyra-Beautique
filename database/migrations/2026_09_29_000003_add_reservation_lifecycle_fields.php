<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // The columns already exist from the run that happened before the
        // working tree was reverted, so each addition is guarded.

        if (!Schema::hasColumn('reservations', 'event_date')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->date('event_date')->nullable()->after('return_date');
            });
        }

        if (!Schema::hasColumn('reservations', 'agreement_version')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->string('agreement_version', 40)->nullable()->after('collateral_status');
            });
        }

        if (!Schema::hasColumn('reservations', 'agreement_accepted_ip')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->string('agreement_accepted_ip', 45)->nullable();
            });
        }

        if (!Schema::hasColumn('reservations', 'payment_reference_number')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->string('payment_reference_number', 80)->nullable();
            });
        }
    }

    public function down(): void
    {
        $columns = array_filter(
            ['event_date', 'agreement_version', 'agreement_accepted_ip', 'payment_reference_number'],
            fn($column) => Schema::hasColumn('reservations', $column)
        );

        if ($columns) {
            Schema::table('reservations', fn(Blueprint $table) => $table->dropColumn($columns));
        }
    }
};
