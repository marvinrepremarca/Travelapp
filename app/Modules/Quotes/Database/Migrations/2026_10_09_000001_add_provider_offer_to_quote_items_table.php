<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('quote_items', function (Blueprint $table): void {
            // Oferta de un proveedor integrado (Search): se re-cotiza y se reserva por esta referencia.
            $table->string('provider_key', 30)->nullable()->after('supplier_id');
            $table->string('provider_offer_id', 191)->nullable()->after('provider_key');
        });
    }

    public function down(): void
    {
        Schema::table('quote_items', function (Blueprint $table): void {
            $table->dropColumn(['provider_key', 'provider_offer_id']);
        });
    }
};
