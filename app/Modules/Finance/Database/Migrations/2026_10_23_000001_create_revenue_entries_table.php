<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Registro de ingresos de Contabilidad (ADR-0007): reconocidos al confirmar cada servicio o registrados a mano.
 * Movimiento financiero: no se edita ni se borra; una cancelación agrega un reverso.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('revenue_entries', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('source', 20);
            $table->string('entry_type', 20);
            // Servicio del expediente que origina el ingreso; con el tipo de movimiento hace idempotente el reconocimiento.
            $table->ulid('booking_item_ulid')->nullable();
            $table->string('booking_number', 30)->nullable();
            $table->string('customer_name')->nullable();
            $table->string('description');
            $table->bigInteger('amount_minor');
            $table->char('currency', 3);
            $table->date('recognized_on');
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['booking_item_ulid', 'entry_type'], 'revenue_entries_item_type_unique');
            $table->index(['recognized_on', 'currency'], 'revenue_entries_recognized_index');
            $table->index(['branch_id', 'recognized_on'], 'revenue_entries_branch_recognized_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revenue_entries');
    }
};
