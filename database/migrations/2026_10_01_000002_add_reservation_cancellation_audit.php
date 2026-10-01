<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            if (!Schema::hasColumn('reservations', 'cancelled_by')) {
                $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('reservations', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable();
            }
            if (!Schema::hasColumn('reservations', 'cancellation_reason')) {
                $table->string('cancellation_reason', 255)->nullable();
            }
            if (!Schema::hasColumn('reservations', 'cancellation_refund_amount')) {
                $table->decimal('cancellation_refund_amount', 10, 2)->default(0);
            }
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('audit_logs', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('audit_logs', 'action')) {
                $table->string('action', 120)->nullable();
            }
            if (!Schema::hasColumn('audit_logs', 'description')) {
                $table->text('description')->nullable();
            }
            if (!Schema::hasColumn('audit_logs', 'ip_address')) {
                $table->string('ip_address', 45)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('reservations', 'cancelled_by')) {
            Schema::table('reservations', fn(Blueprint $table) => $table->dropConstrainedForeignId('cancelled_by'));
        }
        $reservationColumns = array_filter(
            ['cancelled_at', 'cancellation_reason', 'cancellation_refund_amount'],
            fn($column) => Schema::hasColumn('reservations', $column)
        );
        if ($reservationColumns) {
            Schema::table('reservations', fn(Blueprint $table) => $table->dropColumn($reservationColumns));
        }

        if (Schema::hasColumn('audit_logs', 'user_id')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropConstrainedForeignId('user_id');
                $table->dropColumn(['action', 'description', 'ip_address']);
            });
        }
    }
};