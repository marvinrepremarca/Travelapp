<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Conversaciones de WhatsApp con clientes (bot guiado + asesor) y sus mensajes. */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('channel', 30);
            // Teléfono cifrado (dato personal) y su huella HMAC para encontrar la conversación.
            $table->text('contact_phone');
            $table->char('contact_phone_hash', 64);
            $table->string('contact_name')->nullable();
            $table->string('status', 30);
            $table->string('bot_step', 30)->nullable();
            // Respuestas recogidas por el bot (destino, fechas, viajeros).
            $table->json('bot_data');
            $table->ulid('lead_ulid')->nullable();
            // Sin asesor mientras el bot conversa o espera que alguien la tome.
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->dateTime('last_message_at');
            $table->timestamps();

            $table->index(['contact_phone_hash', 'status'], 'conversations_phone_status_index');
            $table->index(['status', 'last_message_at'], 'conversations_status_last_index');
            $table->index(['owner_id', 'status'], 'conversations_owner_status_index');
        });

        Schema::create('conversation_messages', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('conversation_id')->constrained('conversations')->restrictOnDelete();
            $table->string('direction', 20);
            $table->string('author', 20);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            // Plantilla aprobada (mensajes iniciados por la empresa).
            $table->string('template', 60)->nullable();
            // Id del proveedor: los mensajes entrantes son idempotentes por este id.
            $table->string('provider_message_id', 191)->nullable()->unique();
            $table->string('status', 20);
            $table->string('error', 255)->nullable();
            $table->dateTime('sent_at');
            $table->timestamps();

            $table->index(['conversation_id', 'id'], 'conversation_messages_conversation_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_messages');
        Schema::dropIfExists('conversations');
    }
};
