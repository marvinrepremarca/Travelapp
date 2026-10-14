<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Una cotización puede dirigirse a un prospecto (lead) que aún no es cliente: se guardan sus datos de contacto
 * y el cliente se registra cuando la venta se cierra (al crear el expediente).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table): void {
            $table->foreignId('customer_id')->nullable()->change();
            $table->string('prospect_name')->nullable()->after('lead_ulid');
            $table->string('prospect_email')->nullable()->after('prospect_name');
            $table->string('prospect_phone', 30)->nullable()->after('prospect_email');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table): void {
            $table->dropColumn(['prospect_name', 'prospect_email', 'prospect_phone']);
        });
        // Las cotizaciones de prospectos sin cliente no caben en el esquema anterior: no se revierte la nulabilidad.
    }
};
