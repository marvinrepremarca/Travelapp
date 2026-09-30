<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('type', 20);
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('legal_name')->nullable();
            $table->string('display_name')->index();
            $table->string('document_type', 20);
            $table->text('document_number');
            $table->char('document_hash', 64);
            $table->text('birth_date')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone', 30)->nullable()->index();
            $table->string('city', 100)->nullable();
            $table->char('country', 2)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('owner_id')->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['document_type', 'document_hash']);
        });

        Schema::create('customer_consents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('purpose', 30);
            $table->boolean('granted');
            $table->string('channel', 20);
            $table->string('policy_version', 20);
            $table->unsignedBigInteger('recorded_by');
            $table->dateTime('recorded_at');

            $table->index(['customer_id', 'purpose', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_consents');
        Schema::dropIfExists('customers');
    }
};
