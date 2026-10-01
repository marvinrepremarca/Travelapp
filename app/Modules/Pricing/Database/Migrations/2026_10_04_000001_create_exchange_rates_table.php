<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table): void {
            $table->id();
            $table->char('base_currency', 3);
            $table->char('quote_currency', 3);
            $table->decimal('rate', 18, 8);
            $table->string('source', 20);
            $table->date('valid_on');
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->timestamps();

            $table->unique(['base_currency', 'quote_currency', 'source', 'valid_on'], 'exchange_rates_pair_source_day_unique');
            $table->index(['base_currency', 'quote_currency', 'valid_on'], 'exchange_rates_pair_day_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
