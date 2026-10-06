<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Facturas internas y notas (crédito/débito) con consecutivo propio por tipo, listas para facturación electrónica. */
return new class extends Migration {
    /** Tipos de documento con consecutivo (InvoiceType). */
    private const SEQUENCE_TYPES = ['invoice', 'credit_note', 'debit_note'];

    public function up(): void
    {
        Schema::create('invoice_sequences', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 30)->unique();
            $table->unsignedBigInteger('next_number');
            $table->timestamps();
        });

        DB::table('invoice_sequences')->insert(array_map(static fn(string $type): array => ['type' => $type, 'next_number' => 1, 'created_at' => now(), 'updated_at' => now()], self::SEQUENCE_TYPES));

        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('type', 30);
            $table->string('prefix', 10);
            $table->unsignedBigInteger('sequence');
            $table->string('number', 40)->unique();
            // Nota crédito/débito: la factura que afecta.
            $table->foreignId('related_invoice_id')->nullable()->constrained('invoices')->restrictOnDelete();
            $table->ulid('booking_ulid')->index();
            $table->string('booking_number', 30);
            // Solo las facturas lo llenan: un expediente se factura una sola vez.
            $table->ulid('booking_invoice_key')->nullable()->unique();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            // Datos del cliente congelados al emitir (el documento cifrado: dato personal).
            $table->string('customer_name');
            $table->string('customer_document_type', 30);
            $table->text('customer_document_number');
            $table->string('customer_email')->nullable();
            $table->string('customer_city', 100)->nullable();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->char('currency', 3);
            $table->bigInteger('third_party_minor');
            $table->bigInteger('own_income_minor');
            $table->bigInteger('tax_minor');
            $table->bigInteger('total_minor');
            $table->text('reason')->nullable();
            $table->string('e_invoice_status', 30);
            $table->string('e_invoice_reference', 191)->nullable();
            $table->text('e_invoice_message')->nullable();
            $table->foreignId('issued_by')->constrained('users')->restrictOnDelete();
            $table->dateTime('issued_at');
            $table->timestamps();

            $table->unique(['type', 'sequence'], 'invoices_type_sequence_unique');
            $table->index(['type', 'issued_at'], 'invoices_type_issued_index');
            $table->index(['branch_id', 'issued_at'], 'invoices_branch_issued_index');
        });

        Schema::create('invoice_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->restrictOnDelete();
            $table->unsignedSmallInteger('position');
            $table->ulid('booking_item_ulid')->nullable();
            $table->string('description');
            $table->string('product_type', 20)->nullable();
            $table->string('kind', 30);
            $table->bigInteger('amount_minor');
            $table->bigInteger('tax_minor');
            $table->timestamps();

            $table->index(['invoice_id', 'position'], 'invoice_lines_invoice_position_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_lines');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('invoice_sequences');
    }
};
