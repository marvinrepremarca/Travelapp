<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Bitácora de llamadas a proveedores externos (sin payloads sensibles). */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('supplier_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('provider', 50);
            $table->string('operation', 50);
            $table->uuid('correlation_id')->index();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('outcome', 20)->index();
            $table->unsignedInteger('duration_ms');
            $table->string('error', 255)->nullable();
            $table->dateTime('requested_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_requests');
    }
};
