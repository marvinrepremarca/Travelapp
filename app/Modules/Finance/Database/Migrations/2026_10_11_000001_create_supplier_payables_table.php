<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('supplier_payables', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            // Un servicio confirmado genera una sola obligación (idempotente ante eventos repetidos).
            $table->ulid('booking_item_ulid')->unique();
            $table->ulid('booking_ulid')->index();
            $table->string('booking_number', 30);
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('description');
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            $table->date('service_date');
            $table->date('due_date');
            $table->string('status', 30);
            $table->string('payment_reference', 100)->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'due_date'], 'supplier_payables_status_due_index');
            $table->index(['supplier_id', 'status'], 'supplier_payables_supplier_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_payables');
    }
};
