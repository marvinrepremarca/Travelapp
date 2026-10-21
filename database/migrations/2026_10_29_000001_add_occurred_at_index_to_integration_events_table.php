<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* La retención y la fecha de inicio de las capacidades filtran la bitácora por fecha. */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('integration_events', function (Blueprint $table): void {
            $table->index(['occurred_at', 'id'], 'integration_events_occurred_at_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('integration_events', function (Blueprint $table): void {
            $table->dropIndex('integration_events_occurred_at_id_index');
        });
    }
};
