<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 30)->index();
            $table->string('priority', 20);
            $table->dateTime('due_at')->nullable()->index();
            $table->dateTime('remind_at')->nullable();
            $table->dateTime('reminded_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->unsignedBigInteger('owner_id');
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('created_by');
            $table->string('subject_type')->nullable();
            $table->string('subject_id', 36)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['owner_id', 'status']);
            $table->index(['remind_at', 'reminded_at']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
