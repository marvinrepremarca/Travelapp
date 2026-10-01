<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('markup_rules', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('name');
            $table->string('product_type', 30)->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->char('destination_country', 2)->nullable();
            $table->string('sales_channel', 20)->nullable();
            $table->string('kind', 30);
            $table->unsignedInteger('rate_basis_points')->nullable();
            $table->bigInteger('amount_minor')->nullable();
            $table->char('currency', 3)->nullable();
            $table->unsignedInteger('min_margin_basis_points')->nullable();
            $table->integer('priority')->default(0);
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'valid_from'], 'markup_rules_active_from_index');
        });

        Schema::create('fee_rules', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('name');
            $table->string('product_type', 30)->nullable();
            $table->string('sales_channel', 20)->nullable();
            $table->string('basis', 20);
            $table->bigInteger('amount_minor');
            $table->char('currency', 3);
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tax_rules', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('name');
            $table->unsignedInteger('rate_basis_points');
            $table->json('exempt_product_types')->nullable();
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rules');
        Schema::dropIfExists('fee_rules');
        Schema::dropIfExists('markup_rules');
    }
};
