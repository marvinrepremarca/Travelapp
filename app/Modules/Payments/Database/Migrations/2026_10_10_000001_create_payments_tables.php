<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->ulid('booking_ulid')->index();
            // Responsable y sucursal del expediente, copiados para filtrar por alcance sin consultar otro módulo.
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('method', 30);
            $table->string('status', 30);
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            // Comprobante de transferencia o referencia de la pasarela; nunca datos de tarjeta (PCI DSS SAQ-A).
            $table->string('reference', 100)->nullable();
            $table->string('gateway', 30)->nullable();
            $table->string('gateway_reference', 191)->nullable()->unique();
            $table->text('link_url')->nullable();
            $table->dateTime('link_expires_at')->nullable();
            $table->string('idempotency_key', 100)->unique();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['status', 'method'], 'payments_status_method_index');
        });

        Schema::create('payment_gateway_events', function (Blueprint $table): void {
            $table->id();
            $table->string('gateway', 30);
            $table->string('event_id', 191);
            $table->string('gateway_reference', 191);
            $table->string('outcome', 30);
            $table->dateTime('received_at');
            $table->dateTime('processed_at')->nullable();
            $table->timestamps();

            // Un mismo evento repetido por la pasarela se procesa una sola vez.
            $table->unique(['gateway', 'event_id'], 'payment_gateway_events_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_events');
        Schema::dropIfExists('payments');
    }
};
