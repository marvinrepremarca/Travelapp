<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('number', 30)->nullable()->unique();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('title');
            $table->char('sale_currency', 3);
            $table->string('sales_channel', 30);
            $table->string('status', 30);
            $table->unsignedInteger('current_version')->default(0);
            // Instante UTC hasta el que se puede aceptar la última versión enviada.
            $table->dateTime('valid_until')->nullable();
            $table->unsignedBigInteger('accepted_option_id')->nullable();
            $table->unsignedInteger('accepted_version')->nullable();
            $table->dateTime('accepted_at')->nullable();
            $table->string('acceptance_channel', 30)->nullable();
            $table->text('acceptance_note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'valid_until'], 'quotes_status_valid_until_index');
            $table->index(['owner_id', 'status'], 'quotes_owner_status_index');
            $table->index(['branch_id', 'status'], 'quotes_branch_status_index');
        });

        Schema::create('quote_options', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('quote_id')->constrained('quotes')->cascadeOnDelete();
            $table->string('label', 5);
            $table->string('title');
            $table->timestamps();

            $table->unique(['quote_id', 'label'], 'quote_options_quote_label_unique');
        });

        Schema::create('quote_items', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('option_id')->constrained('quote_options')->cascadeOnDelete();
            $table->string('kind', 20);
            $table->string('product_type', 20);
            $table->string('description');
            $table->unsignedBigInteger('catalog_product_id')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->char('destination_country', 2)->nullable();
            // Fecha local del destino; no es un instante.
            $table->date('service_date');
            $table->unsignedSmallInteger('nights')->default(0);
            // Edades a la fecha del servicio: no se filtra por ellas (dato del cálculo).
            $table->json('passenger_ages');
            $table->unsignedBigInteger('net_amount_minor');
            $table->char('net_currency', 3);
            $table->unsignedBigInteger('sale_amount_minor');
            $table->bigInteger('margin_amount_minor');
            // Desglose de Pricing (componentes y tasa de cambio) congelado al calcular.
            $table->json('price_breakdown');
            $table->timestamps();

            $table->index('catalog_product_id', 'quote_items_catalog_product_index');
        });

        Schema::create('quote_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quote_id')->constrained('quotes')->cascadeOnDelete();
            $table->unsignedInteger('version');
            // Copia inmutable de opciones, ítems y precios tal como se enviaron al cliente.
            $table->json('snapshot');
            $table->dateTime('sent_at');
            $table->dateTime('valid_until');
            $table->foreignId('sent_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['quote_id', 'version'], 'quote_versions_quote_version_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_versions');
        Schema::dropIfExists('quote_items');
        Schema::dropIfExists('quote_options');
        Schema::dropIfExists('quotes');
    }
};
