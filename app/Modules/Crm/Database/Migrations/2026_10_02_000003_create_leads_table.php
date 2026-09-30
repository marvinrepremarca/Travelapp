<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('contact_name');
            $table->string('email')->nullable()->index();
            $table->string('phone', 30)->nullable()->index();
            $table->string('channel', 20);
            $table->string('destination')->nullable();
            $table->date('travel_start')->nullable();
            $table->date('travel_end')->nullable();
            $table->unsignedSmallInteger('travelers_count')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 30)->index();
            $table->string('lost_reason', 30)->nullable();
            $table->string('lost_note')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->unsignedBigInteger('owner_id')->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->dateTime('status_changed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('lead_interactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->string('type', 20);
            $table->text('summary');
            $table->dateTime('occurred_at');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->index(['lead_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_interactions');
        Schema::dropIfExists('leads');
    }
};
