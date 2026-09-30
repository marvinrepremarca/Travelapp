<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sensitive_data_accesses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('subject_type');
            $table->string('subject_id', 36);
            $table->string('field', 100);
            $table->string('type', 20);
            $table->string('reason', 255)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->dateTime('accessed_at')->index();

            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sensitive_data_accesses');
    }
};
