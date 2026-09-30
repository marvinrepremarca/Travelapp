<?php

declare(strict_types=1);

namespace App\Modules\Identity\Notifications;

use App\Modules\Identity\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class UserInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        #[\SensitiveParameter]
        public readonly string $token,
        public readonly string $invitedBy,
    ) {}

    /** @return list<string> */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $url = route('password.reset', ['token' => $this->token, 'email' => $notifiable->email]);

        return (new MailMessage())
            ->subject(__('identity.invitation.subject', ['app' => config('app.name')]))
            ->greeting(__('identity.invitation.greeting', ['name' => $notifiable->name]))
            ->line(__('identity.invitation.intro', ['inviter' => $this->invitedBy]))
            ->action(__('identity.invitation.action'), $url)
            ->line(__('identity.invitation.expires', ['minutes' => config('auth.passwords.users.expire')]));
    }
}
