<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* Facturas emitidas a mano a un cliente, sin expediente (ADR-0007): Facturación funciona sin Reservas. */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->ulid('booking_ulid')->nullable()->change();
            $table->string('booking_number', 30)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Las facturas manuales ya emitidas no tienen expediente: no se vuelve a exigir para no perder documentos fiscales.
    }
};
