<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('legal_name');
            $table->string('trade_name')->index();
            $table->string('tax_id', 30)->index();
            $table->char('country', 2);
            $table->boolean('is_tourism_provider')->default(true);
            $table->string('rnt_number', 20)->nullable();
            $table->date('rnt_expires_on')->nullable()->index();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('website')->nullable();
            $table->string('payment_terms', 20);
            $table->unsignedSmallInteger('payment_days');
            $table->char('payment_currency', 3);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['country', 'tax_id']);
        });

        Schema::create('supplier_contacts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->string('name');
            $table->string('position')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->timestamps();
        });

        Schema::create('supplier_bank_accounts', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->string('bank_name');
            $table->string('account_type', 20);
            $table->text('account_number');
            $table->string('holder_name');
            $table->char('currency', 3);
            $table->timestamps();
        });

        Schema::create('supplier_commissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->string('product_type', 30);
            $table->unsignedInteger('rate_basis_points');
            $table->string('base', 10);
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index(['supplier_id', 'product_type', 'valid_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_commissions');
        Schema::dropIfExists('supplier_bank_accounts');
        Schema::dropIfExists('supplier_contacts');
        Schema::dropIfExists('suppliers');
    }
};
