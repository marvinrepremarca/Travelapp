<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('compliance_documents', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('type', 30);
            $table->string('number', 60);
            $table->string('issuer', 120)->nullable();
            $table->date('starts_on')->nullable();
            $table->date('expires_on')->index();
            $table->unsignedBigInteger('responsible_id');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('compliance_obligations', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->date('due_on');
            $table->string('recurrence', 20);
            $table->unsignedBigInteger('responsible_id');
            $table->dateTime('completed_at')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['completed_at', 'due_on']);
        });

        Schema::create('data_subject_requests', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('number', 20)->nullable()->unique();
            $table->string('type', 20);
            $table->string('status', 30);
            $table->text('requester_name');
            $table->text('document_number');
            $table->text('email')->nullable();
            $table->text('phone')->nullable();
            $table->text('details');
            $table->string('channel', 20);
            $table->dateTime('received_at');
            $table->date('due_on');
            $table->text('response')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->unsignedBigInteger('handler_id');
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index(['status', 'due_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_subject_requests');
        Schema::dropIfExists('compliance_obligations');
        Schema::dropIfExists('compliance_documents');
    }
};
