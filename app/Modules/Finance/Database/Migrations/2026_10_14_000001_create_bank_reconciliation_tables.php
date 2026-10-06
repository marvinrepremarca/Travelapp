<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Conciliación bancaria: cuentas de la agencia, extractos cargados (CSV) y sus líneas cruzadas con el sistema. */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('agency_bank_accounts', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('name', 100);
            $table->string('bank_name', 100);
            // Solo los últimos dígitos: el número completo no se necesita para conciliar.
            $table->string('account_last_digits', 4);
            $table->char('currency', 3);
            // Cómo leer el CSV de este banco (columnas por encabezado, separadores, formato de fecha).
            $table->json('statement_format');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('bank_statements', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('agency_bank_account_id')->constrained('agency_bank_accounts')->restrictOnDelete();
            $table->string('file_name', 191);
            // Huella del archivo: el mismo extracto no se carga dos veces.
            $table->char('file_hash', 64);
            $table->unsignedInteger('lines_imported');
            $table->unsignedInteger('lines_skipped');
            $table->date('first_posted_on')->nullable();
            $table->date('last_posted_on')->nullable();
            $table->foreignId('imported_by')->constrained('users')->restrictOnDelete();
            $table->dateTime('imported_at');
            $table->timestamps();

            $table->unique(['agency_bank_account_id', 'file_hash'], 'bank_statements_account_hash_unique');
        });

        Schema::create('bank_statement_lines', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('bank_statement_id')->constrained('bank_statements')->restrictOnDelete();
            $table->foreignId('agency_bank_account_id')->constrained('agency_bank_accounts')->restrictOnDelete();
            $table->date('posted_on');
            $table->string('description', 255);
            $table->string('reference', 100)->nullable();
            // Con signo: positivo = entra al banco, negativo = sale.
            $table->bigInteger('amount_minor');
            $table->char('currency', 3);
            // Huella del movimiento: extractos que se solapan no duplican líneas.
            $table->char('line_hash', 64);
            $table->string('status', 30);
            $table->string('matched_target', 30)->nullable();
            $table->ulid('matched_ulid')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();

            $table->unique(['agency_bank_account_id', 'line_hash'], 'bank_statement_lines_account_hash_unique');
            // Cada movimiento del sistema se concilia con una sola línea del banco.
            $table->unique(['matched_target', 'matched_ulid'], 'bank_statement_lines_match_unique');
            $table->index(['agency_bank_account_id', 'status', 'posted_on'], 'bank_statement_lines_account_status_index');
        });

        Schema::table('supplier_payables', function (Blueprint $table): void {
            // Agrupa las obligaciones pagadas en una misma liquidación (una salida del banco).
            $table->ulid('settlement_ulid')->nullable()->after('payment_reference')->index();
        });
    }

    public function down(): void
    {
        Schema::table('supplier_payables', function (Blueprint $table): void {
            $table->dropIndex(['settlement_ulid']);
            $table->dropColumn('settlement_ulid');
        });
        Schema::dropIfExists('bank_statement_lines');
        Schema::dropIfExists('bank_statements');
        Schema::dropIfExists('agency_bank_accounts');
    }
};
