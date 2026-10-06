<?php

declare(strict_types=1);

namespace App\Modules\Communications\Services;

use App\Modules\Communications\Enums\ConversationStatus;
use App\Modules\Communications\Enums\MessageAuthor;
use App\Modules\Communications\Enums\NoticeTemplate;
use App\Modules\Communications\Models\Conversation;
use App\Modules\Communications\Models\ConversationMessage;
use App\Modules\Crm\Contracts\CustomerContacts;
use App\Modules\Crm\Data\CustomerWhatsApp;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Envía un aviso por WhatsApp al cliente (solo si tiene teléfono y autorizó el tratamiento de datos) dentro de su
 * conversación abierta, o abre una a cargo del asesor responsable para que la respuesta del cliente le llegue a él.
 * Un aviso por clave: reintentos y procesos programados no lo repiten.
 */
final readonly class CustomerNotifier
{
    public function __construct(
        private CustomerContacts $contacts,
        private PhoneNumbers $phones,
        private MessageOutbox $outbox,
    ) {}

    /** @param  array<string, string>  $params */
    public function notify(int $customerId, int $ownerId, ?int $branchId, NoticeTemplate $template, array $params, string $dedupeKey): ?ConversationMessage
    {
        $contact = $this->contacts->whatsApp($customerId);
        if (! $contact instanceof CustomerWhatsApp || ConversationMessage::query()->where('dedupe_key', $dedupeKey)->exists()) {
            return null;
        }

        try {
            return DB::transaction(function () use ($contact, $ownerId, $branchId, $template, $params, $dedupeKey): ConversationMessage {
                $conversation = $this->conversationFor($contact, $ownerId, $branchId);
                $message = $this->outbox->queue($conversation, MessageAuthor::System, $template->render(['name' => $contact->name, ...$params]), null, $template->value);
                $message->dedupe_key = $dedupeKey;
                $message->save();

                return $message;
            });
        } catch (UniqueConstraintViolationException) {
            return null;
        }
    }

    private function conversationFor(CustomerWhatsApp $contact, int $ownerId, ?int $branchId): Conversation
    {
        $hash = $this->phones->hash($contact->phone);
        $open = Conversation::query()->where('contact_phone_hash', $hash)->where('status', '!=', ConversationStatus::Closed)->lockForUpdate()->latest('id')->first();
        if ($open instanceof Conversation) {
            return $open;
        }

        $conversation = new Conversation();
        $conversation->forceFill([
            'channel' => config()->string('travel.communications.whatsapp_channel'),
            'contact_phone' => $this->phones->normalize($contact->phone),
            'contact_phone_hash' => $hash,
            'contact_name' => $contact->name,
            'status' => ConversationStatus::WithAgent,
            'bot_step' => null,
            'bot_data' => [],
            'owner_id' => $ownerId,
            'branch_id' => $branchId,
            'last_message_at' => CarbonImmutable::now(),
        ])->save();

        return $conversation;
    }
}
