<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('booking_items', function (Blueprint $table): void {
            // Política pactada con el cliente (tramos por días de anticipación); no se filtra por ella.
            $table->json('cancellation_policy')->nullable()->after('price_breakdown');
            // Penalidad calculada al cancelar, en la moneda de venta; el reembolso lo gestiona Finanzas.
            $table->unsignedBigInteger('penalty_amount_minor')->nullable()->after('cancellation_policy');
        });
    }

    public function down(): void
    {
        Schema::table('booking_items', function (Blueprint $table): void {
            $table->dropColumn(['cancellation_policy', 'penalty_amount_minor']);
        });
    }
};
