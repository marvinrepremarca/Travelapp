<?php

declare(strict_types=1);

use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Organization\Models\Branch;
use App\Modules\Workflow\Actions\CancelApprovalAction;
use App\Modules\Workflow\Actions\ExpireApprovalsAction;
use App\Modules\Workflow\Actions\ResolveApprovalAction;
use App\Modules\Workflow\Contracts\Approvals;
use App\Modules\Workflow\Data\ApprovalRequestData;
use App\Modules\Workflow\Enums\ApprovalStatus;
use App\Modules\Workflow\Enums\ApprovalType;
use App\Modules\Workflow\Events\ApprovalRequested;
use App\Modules\Workflow\Events\ApprovalResolved;
use App\Modules\Workflow\Exceptions\InvalidApproval;
use App\Modules\Workflow\Livewire\ApprovalsInbox;
use App\Modules\Workflow\Models\ApprovalRequest;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

function requestDiscount(User $requester, ?CarbonImmutable $expiresAt = null): ApprovalRequest
{
    return app(Approvals::class)->request(new ApprovalRequestData(
        type: ApprovalType::Discount,
        subject: Branch::factory()->create(),
        summary: 'Descuento del 15 % en expediente',
        justification: 'Cliente frecuente',
        context: ['percent' => '15'],
        expiresAt: $expiresAt,
    ), $requester);
}

it('lets a requester ask for approval and announces it', function (): void {
    Event::fake([ApprovalRequested::class]);

    $approval = requestDiscount(agent());

    expect($approval->status)->toBe(ApprovalStatus::Pending)
        ->and($approval->context)->toBe(['percent' => '15']);
    Event::assertDispatched(ApprovalRequested::class, fn(ApprovalRequested $event): bool => $event->approvalUlid === $approval->ulid);
});

it('does not allow two pending requests for the same action', function (): void {
    $agent = agent();
    $subject = Branch::factory()->create();
    $data = new ApprovalRequestData(ApprovalType::Refund, $subject, 'Reembolso');

    app(Approvals::class)->request($data, $agent);

    expect(fn() => app(Approvals::class)->request($data, $agent))
        ->toThrow(InvalidApproval::class, __('workflow.errors.approval_already_pending'));
});

it('lets a branch manager approve discounts of their branch', function (): void {
    Event::fake([ApprovalResolved::class]);
    $manager = branchManager();
    $agent = User::factory()->withRole(Role::TravelAgent)->for($manager->branch)->create();
    $approval = requestDiscount($agent);
    actingAs($manager);

    Livewire::test(ApprovalsInbox::class)
        ->assertSee($approval->summary)
        ->set("notes.{$approval->ulid}", 'Autorizado por fidelidad')
        ->call('approve', $approval->ulid)
        ->assertHasNoErrors();

    $approval->refresh();
    expect($approval->status)->toBe(ApprovalStatus::Approved)
        ->and($approval->decided_by)->toBe($manager->id)
        ->and($approval->decision_note)->toBe('Autorizado por fidelidad');
    Event::assertDispatched(ApprovalResolved::class, fn(ApprovalResolved $event): bool => $event->status === ApprovalStatus::Approved);
});

it('hides approvals of other branches and types without permission', function (): void {
    $manager = branchManager();
    $otherBranch = requestDiscount(User::factory()->withRole(Role::TravelAgent)->create());
    $refund = app(Approvals::class)->request(new ApprovalRequestData(ApprovalType::Refund, Branch::factory()->create(), 'Reembolso ajeno'), User::factory()->for($manager->branch)->create());
    actingAs($manager);

    Livewire::test(ApprovalsInbox::class)
        ->assertDontSee($otherBranch->summary)
        ->assertDontSee('Reembolso ajeno')
        ->call('approve', $otherBranch->ulid)
        ->assertNotFound();

    Livewire::test(ApprovalsInbox::class)->call('approve', $refund->ulid)->assertHasErrors("notes.{$refund->ulid}");
    expect($refund->fresh()?->status)->toBe(ApprovalStatus::Pending);
});

it('requires a note to reject', function (): void {
    $owner = userWithRole(Role::AgencyOwner);
    $approval = requestDiscount(agent());
    actingAs($owner);

    Livewire::test(ApprovalsInbox::class)
        ->call('reject', $approval->ulid)
        ->assertHasErrors(["notes.{$approval->ulid}" => __('workflow.errors.approval_note_required')])
        ->set("notes.{$approval->ulid}", 'Supera el margen mínimo')
        ->call('reject', $approval->ulid)
        ->assertHasNoErrors();

    expect($approval->fresh()?->status)->toBe(ApprovalStatus::Rejected);
});

it('never lets someone decide their own request', function (): void {
    $owner = userWithRole(Role::AgencyOwner);
    $approval = requestDiscount($owner);

    expect(fn() => app(ResolveApprovalAction::class)->execute($approval, ApprovalStatus::Approved, $owner))
        ->toThrow(InvalidApproval::class, __('workflow.errors.approval_own_request'));
});

it('rejects decisions on resolved or expired requests and invalid decisions', function (): void {
    $owner = userWithRole(Role::AgencyOwner);
    $resolve = app(ResolveApprovalAction::class);

    $resolved = requestDiscount(agent());
    $resolve->execute($resolved, ApprovalStatus::Approved, $owner);
    expect(fn() => $resolve->execute($resolved, ApprovalStatus::Approved, $owner))->toThrow(InvalidApproval::class);

    $expired = requestDiscount(agent(), CarbonImmutable::now()->subMinute());
    expect(fn() => $resolve->execute($expired, ApprovalStatus::Approved, $owner))->toThrow(InvalidApproval::class, __('workflow.errors.approval_expired'));

    expect(fn() => $resolve->execute(requestDiscount(agent()), ApprovalStatus::Cancelled, $owner))->toThrow(InvalidApproval::class, __('workflow.errors.approval_invalid_decision'));
});

it('lets only the requester withdraw a pending request', function (): void {
    Event::fake([ApprovalResolved::class]);
    $agent = agent();
    $approval = requestDiscount($agent);
    actingAs($agent);

    Livewire::test(ApprovalsInbox::class)
        ->assertSee(__('workflow.approvals.my_requests'))
        ->call('cancel', $approval->ulid);

    expect($approval->fresh()?->status)->toBe(ApprovalStatus::Cancelled);
    Event::assertDispatched(ApprovalResolved::class, fn(ApprovalResolved $event): bool => $event->status === ApprovalStatus::Cancelled);

    expect(fn() => app(CancelApprovalAction::class)->execute($approval->fresh(), $agent))->toThrow(InvalidApproval::class);
    expect(fn() => app(CancelApprovalAction::class)->execute(requestDiscount(agent()), $agent))->toThrow(InvalidApproval::class, __('workflow.errors.approval_not_owner'));

    Livewire::test(ApprovalsInbox::class)->call('cancel', ApprovalRequest::query()->where('owner_id', '!=', $agent->id)->firstOrFail()->ulid)->assertNotFound();
});

it('shows cancel errors inline', function (): void {
    $agent = agent();
    $approval = requestDiscount($agent);
    app(CancelApprovalAction::class)->execute($approval, $agent);
    actingAs($agent);

    Livewire::test(ApprovalsInbox::class)->call('cancel', $approval->ulid)->assertHasErrors("notes.{$approval->ulid}");
});

it('expires overdue pending requests', function (): void {
    Event::fake([ApprovalResolved::class]);
    $overdue = requestDiscount(agent(), CarbonImmutable::now()->subMinute());
    $current = requestDiscount(agent(), CarbonImmutable::now()->addDay());

    expect(app(ExpireApprovalsAction::class)->execute(CarbonImmutable::now()))->toBe(1)
        ->and($overdue->fresh()?->status)->toBe(ApprovalStatus::Expired)
        ->and($current->fresh()?->status)->toBe(ApprovalStatus::Pending);
    Event::assertDispatched(ApprovalResolved::class, fn(ApprovalResolved $event): bool => $event->status === ApprovalStatus::Expired);

    $this->artisan('workflow:expire-approvals')->assertSuccessful();
});

it('shows empty states', function (): void {
    actingAs(agent());

    Livewire::test(ApprovalsInbox::class)
        ->assertSee(__('workflow.approvals.nothing_pending'))
        ->assertSee(__('workflow.approvals.no_requests'));
});

it('maps each approval type to an approver permission and labels everything', function (): void {
    foreach (ApprovalType::cases() as $type) {
        expect($type->label())->not->toStartWith('workflow.')
            ->and($type->approverPermission()->label())->not->toStartWith('shared.');
    }

    foreach (ApprovalStatus::cases() as $status) {
        expect($status->label())->not->toStartWith('workflow.');
    }

    expect(ApprovalStatus::Rejected->tone()->value)->toBe('danger')
        ->and(ApprovalStatus::Expired->tone()->value)->toBe('neutral')
        ->and(InvalidApproval::expired()->errorCode())->toBe('invalid_approval');
});

it('schedules reminders and approval expiration', function (): void {
    $commands = collect(app(Illuminate\Console\Scheduling\Schedule::class)->events())->map(fn($event): string => (string) $event->command);

    expect($commands->contains(fn(string $command): bool => str_contains($command, 'workflow:send-task-reminders')))->toBeTrue()
        ->and($commands->contains(fn(string $command): bool => str_contains($command, 'workflow:expire-approvals')))->toBeTrue();
});

it('identifies tasks and approvals publicly by ulid', function (): void {
    expect(ApprovalRequest::factory()->create()->getRouteKeyName())->toBe('ulid')
        ->and(App\Modules\Workflow\Models\Task::factory()->create()->getRouteKeyName())->toBe('ulid');
});
