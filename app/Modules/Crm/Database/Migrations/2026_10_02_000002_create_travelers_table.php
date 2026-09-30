<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('travelers', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->char('gender', 1);
            $table->text('birth_date');
            $table->char('nationality', 2);
            $table->text('passport_number')->nullable();
            $table->char('passport_hash', 64)->nullable()->index();
            $table->char('passport_country', 2)->nullable();
            $table->date('passport_expires_on')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travelers');
    }
};
