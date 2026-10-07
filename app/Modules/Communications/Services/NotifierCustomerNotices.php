<?php

declare(strict_types=1);

namespace App\Modules\Communications\Services;

use App\Modules\Communications\Contracts\CustomerNotices;
use App\Modules\Communications\Enums\NoticeTemplate;
use App\Modules\Communications\Models\ConversationMessage;

final readonly class NotifierCustomerNotices implements CustomerNotices
{
    public function __construct(private CustomerNotifier $notifier) {}

    public function send(int $customerId, int $ownerId, ?int $branchId, NoticeTemplate $template, array $params, string $dedupeKey): bool
    {
        return $this->notifier->notify($customerId, $ownerId, $branchId, $template, $params, $dedupeKey) instanceof ConversationMessage;
    }
}
