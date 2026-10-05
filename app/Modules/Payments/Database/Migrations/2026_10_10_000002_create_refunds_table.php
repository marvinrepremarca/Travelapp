<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->ulid('booking_ulid')->index();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            $table->text('reason');
            $table->string('status', 30);
            $table->ulid('approval_ulid')->nullable();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->dateTime('decided_at')->nullable();
            // Comprobante del pago del reembolso al cliente.
            $table->string('payout_reference', 100)->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'booking_ulid'], 'refunds_status_booking_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
