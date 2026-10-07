<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Actions;

use App\Modules\Compliance\Data\DataRequestData;
use App\Modules\Compliance\Enums\DataRequestStatus;
use App\Modules\Compliance\Models\DataSubjectRequest;
use App\Modules\Compliance\Services\ComplianceTasks;
use App\Modules\Identity\Models\User;
use App\Modules\Organization\Contracts\HolidayCalendar;
use App\Modules\Workflow\Enums\TaskPriority;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Radica la solicitud de un titular (Ley 1581): plazo de respuesta en días hábiles ⚙ desde la fecha de recibo
 * (sin fines de semana ni festivos), consecutivo propio y tarea para el oficial de protección de datos ⚙.
 */
final readonly class RegisterDataRequestAction
{
    public function __construct(private HolidayCalendar $calendar, private ComplianceTasks $tasks) {}

    public function execute(User $actor, DataRequestData $data): DataSubjectRequest
    {
        $timezone = config()->string('travel.agency.timezone');
        $receivedLocal = $data->receivedAt->timezone($timezone)->startOfDay();
        $dueOn = $this->calendar->addBusinessDays($receivedLocal, config()->integer('travel.compliance.request_business_days'));
        $handler = $this->handler($actor);

        return DB::transaction(function () use ($actor, $data, $dueOn, $handler): DataSubjectRequest {
            $request = new DataSubjectRequest([
                'type' => $data->type,
                'requester_name' => $data->requesterName,
                'document_number' => $data->documentNumber,
                'email' => $data->email,
                'phone' => $data->phone,
                'details' => $data->details,
                'channel' => $data->channel,
            ]);
            $request->status = DataRequestStatus::Received;
            $request->received_at = $data->receivedAt->utc();
            $request->due_on = $dueOn;
            $request->handler_id = $handler->id;
            $request->created_by = $actor->id;
            $request->save();

            $request->number = config()->string('travel.compliance.request_number_prefix')
                . str_pad((string) $request->id, config()->integer('travel.compliance.request_number_digits'), '0', STR_PAD_LEFT);
            $request->save();

            $this->tasks->schedule(
                __('compliance.requests.task', ['number' => $request->number, 'type' => $data->type->label()]),
                $handler->id,
                $dueOn,
                $this->alertCalendarDays($dueOn),
                $request,
                $actor,
                TaskPriority::Urgent,
            );

            return $request;
        });
    }

    private function handler(User $actor): User
    {
        $email = config('travel.compliance.privacy_officer_email');
        if (! is_string($email) || $email === '') {
            return $actor;
        }

        return User::query()->where('email', $email)->where('is_active', true)->first() ?? $actor;
    }

    /** Días calendario entre el aviso (N días hábiles antes ⚙) y el vencimiento. */
    private function alertCalendarDays(CarbonImmutable $dueOn): int
    {
        $alert = $dueOn;
        for ($left = config()->integer('travel.compliance.request_alert_business_days'); $left > 0;) {
            $alert = $alert->subDay();
            if ($this->calendar->isBusinessDay($alert)) {
                $left--;
            }
        }

        return (int) $alert->diffInDays($dueOn, true);
    }
}
