<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Prospecto (Comercial) que originó la cotización. Es una referencia por ULID, sin llave foránea, para que
 * Cotizaciones no dependa de las tablas de Comercial (ADR-0007): al enviarse, Comercial marca el prospecto cotizado.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table): void {
            $table->char('lead_ulid', 26)->nullable()->after('customer_id')->index('quotes_lead_ulid_index');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table): void {
            $table->dropIndex('quotes_lead_ulid_index');
            $table->dropColumn('lead_ulid');
        });
    }
};
