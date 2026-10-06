<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Notas crédito solicitadas (pasan por aprobación de finanzas) y trazabilidad de qué línea acredita cada nota. */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoice_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('invoice_id')->constrained('invoices')->restrictOnDelete();
            $table->string('status', 30);
            $table->text('reason');
            // Monto a acreditar por línea de la factura: [{"invoice_line_id": 1, "amount_minor": 1000}].
            $table->json('lines');
            $table->bigInteger('total_minor');
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->ulid('approval_ulid')->nullable();
            $table->foreignId('note_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->dateTime('decided_at')->nullable();
            $table->timestamps();

            $table->index(['invoice_id', 'status'], 'invoice_adjustments_invoice_status_index');
        });

        Schema::table('invoice_lines', function (Blueprint $table): void {
            // En una nota crédito: la línea de la factura que acredita.
            $table->foreignId('source_line_id')->nullable()->after('booking_item_ulid')->constrained('invoice_lines')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoice_lines', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('source_line_id');
        });
        Schema::dropIfExists('invoice_adjustments');
    }
};
