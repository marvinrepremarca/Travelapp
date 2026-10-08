<?php

declare(strict_types=1);

use App\Modules\Compliance\Actions\CompleteObligationAction;
use App\Modules\Compliance\Actions\RegisterDataRequestAction;
use App\Modules\Compliance\Actions\ResolveDataRequestAction;
use App\Modules\Compliance\Actions\SaveObligationAction;
use App\Modules\Compliance\Data\DataRequestData;
use App\Modules\Compliance\Data\ObligationData;
use App\Modules\Compliance\Enums\DataRequestStatus;
use App\Modules\Compliance\Enums\DataRequestType;
use App\Modules\Compliance\Enums\ObligationRecurrence;
use App\Modules\Compliance\Exceptions\ComplianceRuleViolation;
use App\Modules\Compliance\Livewire\ComplianceOverview;
use App\Modules\Compliance\Livewire\DataRequestForm;
use App\Modules\Compliance\Livewire\DataRequestShow;
use App\Modules\Compliance\Livewire\DataRequestsIndex;
use App\Modules\Compliance\Livewire\DocumentForm;
use App\Modules\Compliance\Livewire\DocumentsIndex;
use App\Modules\Compliance\Livewire\ObligationForm;
use App\Modules\Compliance\Models\ComplianceDocument;
use App\Modules\Compliance\Models\ComplianceObligation;
use App\Modules\Compliance\Models\DataSubjectRequest;
use App\Modules\Customers\Enums\ConsentChannel;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Workflow\Models\Task;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-11-03 14:00:00', 'America/Bogota'));
});

function complianceOfficer(): User
{
    return userWithRole(Role::Finance);
}

function dataRequest(User $actor, string $receivedAt = '2026-11-03 09:00'): DataSubjectRequest
{
    return app(RegisterDataRequestAction::class)->execute($actor, new DataRequestData(
        DataRequestType::Deletion,
        'Laura Gómez',
        '52123456',
        'laura@example.test',
        null,
        'Solicito suprimir mis datos de la base de mercadeo.',
        ConsentChannel::Email,
        CarbonImmutable::parse($receivedAt, 'America/Bogota'),
    ));
}

it('registers documents with renewal tasks and alerts before they expire', function (): void {
    $officer = complianceOfficer();
    actingAs($officer);

    Livewire::test(DocumentForm::class)
        ->set('number', '')
        ->set('expires_on', '')
        ->call('save')
        ->assertHasErrors(['number', 'expires_on'])
        ->set('number', 'RNT-12345')
        ->set('issuer', 'Confecámaras')
        ->set('starts_on', '2026-03-31')
        ->set('expires_on', '2026-12-15')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('compliance.documents'));

    $document = ComplianceDocument::query()->sole();
    $task = Task::query()->sole();
    expect($task->owner_id)->toBe($officer->id)
        ->and($task->title)->toContain('RNT-12345')
        ->and($task->remind_at?->toDateString())->toBe('2026-11-03');

    Livewire::test(DocumentsIndex::class)->assertSee('RNT-12345')->assertSee(trans_choice('compliance.documents.expires_in', 42, ['days' => 42]));
    Livewire::test(ComplianceOverview::class)->assertSee(__('compliance.overview.documents_hint', ['expired' => 0, 'expiring' => 1, 'days' => 60]));

    Livewire::test(DocumentForm::class, ['document' => $document])->set('notes', 'Renovado')->call('save')->assertHasNoErrors();
    expect(Task::query()->count())->toBe(1);
});

it('keeps the obligations calendar and schedules the next occurrence when completed', function (): void {
    $officer = complianceOfficer();
    actingAs($officer);

    Livewire::test(ObligationForm::class)
        ->call('save')
        ->assertHasErrors(['title', 'due_on'])
        ->set('title', 'Declaración contribución FONTUR')
        ->set('due_on', '2026-11-01')
        ->set('recurrence', ObligationRecurrence::Quarterly->value)
        ->call('save')
        ->assertHasNoErrors();
    $obligation = ComplianceObligation::query()->sole();

    Livewire::test(ComplianceOverview::class)
        ->assertSee('Declaración contribución FONTUR')
        ->assertSee(__('compliance.calendar.overdue'))
        ->call('complete', $obligation->ulid)
        ->assertSee(__('compliance.obligations.completed_next', ['date' => CarbonImmutable::parse('2027-02-01')->isoFormat('LL')]));

    $next = ComplianceObligation::query()->whereNull('completed_at')->sole();
    expect($obligation->fresh()?->completed_at)->not->toBeNull()
        ->and($next->due_on->toDateString())->toBe('2027-02-01')
        ->and(Task::query()->count())->toBe(2)
        ->and(fn() => app(CompleteObligationAction::class)->execute($officer, $obligation))->toThrow(ComplianceRuleViolation::class);

    $once = app(SaveObligationAction::class)->execute($officer, new ObligationData('Reporte único', null, CarbonImmutable::parse('2026-11-20'), ObligationRecurrence::Once, $officer->id));
    expect(app(CompleteObligationAction::class)->execute($officer, $once))->toBeNull();
});

it('computes the Ley 1581 deadline in business days and encrypts the requester data', function (): void {
    $officer = complianceOfficer();
    $request = dataRequest($officer);

    // 15 días hábiles desde el 3 de noviembre de 2026: se salta fines de semana y el festivo del 16 de noviembre.
    expect($request->due_on->toDateString())->toBe('2026-11-25')
        ->and($request->number)->toBe('SOL-' . str_pad((string) $request->id, 6, '0', STR_PAD_LEFT))
        ->and($request->status)->toBe(DataRequestStatus::Received)
        ->and(DB::table('data_subject_requests')->value('document_number'))->not->toContain('52123456')
        ->and(Task::query()->sole()->owner_id)->toBe($officer->id);

    config()->set('travel.compliance.privacy_officer_email', $other = userWithRole(Role::AgencyOwner)->email);
    expect(dataRequest($officer)->handler_id)->toBe(User::query()->where('email', $other)->value('id'));
});

it('answers data requests, requires a response and audits revealing personal data', function (): void {
    $officer = complianceOfficer();
    $request = dataRequest($officer);
    actingAs($officer);

    Livewire::test(DataRequestsIndex::class)->assertSee((string) $request->number)->assertDontSee('Laura Gómez');

    Livewire::test(DataRequestShow::class, ['request' => $request])
        ->assertDontSee('52123456')
        ->call('reveal')
        ->assertSee('52123456')
        ->set('status', DataRequestStatus::Answered->value)
        ->call('resolve')
        ->assertHasErrors('response')
        ->set('response', 'Datos suprimidos de la base de mercadeo el 2026-11-04.')
        ->call('resolve')
        ->assertHasNoErrors()
        ->assertSee(__('compliance.requests.updated'));

    expect($request->fresh()?->status)->toBe(DataRequestStatus::Answered)
        ->and(DB::table('activity_log')->where('description', 'personal_data_revealed')->count())->toBe(1)
        ->and(fn() => app(ResolveDataRequestAction::class)->execute($officer, $request->fresh(), DataRequestStatus::InProgress, null))->toThrow(ComplianceRuleViolation::class);
    Livewire::test(DataRequestsIndex::class)->assertDontSee((string) $request->number)->set('onlyOpen', false)->assertSee((string) $request->number);
});

it('registers data requests from the form and flags them as they approach the deadline', function (): void {
    actingAs(complianceOfficer());

    Livewire::test(DataRequestForm::class)
        ->call('save')
        ->assertHasErrors(['requester_name', 'document_number', 'details', 'email'])
        ->set('requester_name', 'Pedro Ruiz')
        ->set('document_number', '1020304050')
        ->set('phone', '+57 300 123 4567')
        ->set('details', 'Quiero saber qué datos tienen de mí.')
        ->set('received_on', '2026-11-03')
        ->call('save')
        ->assertHasNoErrors();

    $request = DataSubjectRequest::query()->sole();
    $this->travelTo(CarbonImmutable::parse('2026-11-23 10:00:00', 'America/Bogota'));
    Livewire::test(DataRequestsIndex::class)->assertSee(__('compliance.requests.due_soon'));
    Livewire::test(ComplianceOverview::class)->assertSee(__('compliance.overview.requests_hint', ['overdue' => 0, 'due' => 1, 'days' => 3]));

    $this->travelTo(CarbonImmutable::parse('2026-11-26 10:00:00', 'America/Bogota'));
    Livewire::test(DataRequestShow::class, ['request' => $request])->assertSee(__('compliance.requests.overdue'));
});

it('restricts compliance to users with the compliance permission', function (): void {
    foreach (['compliance.index', 'compliance.documents', 'compliance.requests', 'compliance.requests.create', 'compliance.obligations.create'] as $route) {
        actingAs(agent())->get(route($route))->assertForbidden();
    }
    actingAs(complianceOfficer())->get(route('compliance.index'))->assertOk();
    actingAs(complianceOfficer())->get(route('compliance.requests.show', 'no-existe'))->assertNotFound();
});
