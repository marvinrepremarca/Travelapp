<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cash_sessions', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            // Igual a branch_id mientras está abierta y NULL al cerrar: una sola caja abierta por sucursal (MySQL y MariaDB).
            $table->unsignedBigInteger('open_branch_key')->nullable()->unique();
            $table->char('currency', 3);
            $table->unsignedBigInteger('opening_amount_minor');
            $table->foreignId('opened_by')->constrained('users')->restrictOnDelete();
            $table->dateTime('opened_at');
            $table->string('status', 30);
            $table->bigInteger('expected_amount_minor')->nullable();
            $table->bigInteger('counted_amount_minor')->nullable();
            $table->bigInteger('difference_minor')->nullable();
            $table->text('closing_note')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('closed_at')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'opened_at'], 'cash_sessions_branch_opened_index');
        });

        Schema::create('cash_movements', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('cash_session_id')->constrained('cash_sessions')->restrictOnDelete();
            $table->string('type', 20);
            $table->unsignedBigInteger('amount_minor');
            $table->string('description');
            // Abono en efectivo que originó la entrada (un abono, una entrada).
            $table->ulid('payment_ulid')->nullable()->unique();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->dateTime('recorded_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_movements');
        Schema::dropIfExists('cash_sessions');
    }
};
