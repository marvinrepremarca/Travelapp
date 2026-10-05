<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('booking_items', function (Blueprint $table): void {
            $table->string('provider_key', 30)->nullable()->after('supplier_id');
            $table->string('provider_offer_id', 191)->nullable()->after('provider_key');
            // Referencia de la reserva en el proveedor (para consultar y cancelar).
            $table->string('provider_booking_reference', 191)->nullable()->after('provider_offer_id');
        });
    }

    public function down(): void
    {
        Schema::table('booking_items', function (Blueprint $table): void {
            $table->dropColumn(['provider_key', 'provider_offer_id', 'provider_booking_reference']);
        });
    }
};
