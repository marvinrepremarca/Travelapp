<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Abonos de un cliente sin expediente (ADR-0007): anticipos o ventas externas; Cobros funciona sin Reservas.
 * Se identifica al cliente y el concepto; los abonos de expediente siguen igual.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->ulid('booking_ulid')->nullable()->change();
            $table->foreignId('customer_id')->nullable()->after('booking_ulid')->constrained('customers')->restrictOnDelete();
            $table->string('concept')->nullable()->after('customer_id');
            $table->index(['customer_id', 'status'], 'payments_customer_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            // La llave foránea usa el índice compuesto: se suelta primero.
            $table->dropForeign(['customer_id']);
            $table->dropIndex('payments_customer_status_index');
            $table->dropColumn(['customer_id', 'concept']);
        });
    }
};
