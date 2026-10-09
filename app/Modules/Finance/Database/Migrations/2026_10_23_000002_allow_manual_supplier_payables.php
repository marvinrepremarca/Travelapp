<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Cuentas por pagar registradas a mano (ADR-0007): sin expediente, con origen explícito.
 * Las existentes nacieron de servicios confirmados.
 */
return new class extends Migration {
    private const BOOKING_SOURCE = 'booking';

    public function up(): void
    {
        Schema::table('supplier_payables', function (Blueprint $table): void {
            $table->string('source', 20)->default(self::BOOKING_SOURCE)->after('ulid');
            $table->ulid('booking_item_ulid')->nullable()->change();
            $table->ulid('booking_ulid')->nullable()->change();
            $table->string('booking_number', 30)->nullable()->change();
            $table->date('service_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('supplier_payables', function (Blueprint $table): void {
            $table->dropColumn('source');
        });
    }
};
