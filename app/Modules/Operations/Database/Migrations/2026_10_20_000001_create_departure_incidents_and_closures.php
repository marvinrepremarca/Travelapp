<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('departure_incidents', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->ulid('departure_ulid');
            $table->string('severity', 10);
            $table->text('description');
            $table->unsignedBigInteger('reported_by');
            $table->dateTime('reported_at');
            $table->text('resolution')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['departure_ulid', 'resolved_at']);
        });

        Schema::create('departure_closures', function (Blueprint $table): void {
            $table->id();
            $table->ulid('departure_ulid')->unique();
            $table->unsignedSmallInteger('attended');
            $table->unsignedSmallInteger('no_shows');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('closed_by');
            $table->dateTime('closed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departure_closures');
        Schema::dropIfExists('departure_incidents');
    }
};
