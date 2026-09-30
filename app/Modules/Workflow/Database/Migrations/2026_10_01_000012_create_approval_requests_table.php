<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('approval_requests', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('type', 40);
            $table->string('status', 30)->index();
            $table->string('subject_type');
            $table->string('subject_id', 36);
            $table->string('summary');
            $table->text('justification')->nullable();
            $table->json('context')->nullable();
            $table->unsignedBigInteger('owner_id')->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('decided_by')->nullable();
            $table->text('decision_note')->nullable();
            $table->dateTime('decided_at')->nullable();
            $table->dateTime('expires_at')->nullable()->index();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_requests');
    }
};
