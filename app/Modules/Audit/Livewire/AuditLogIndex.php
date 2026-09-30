<?php

declare(strict_types=1);

namespace App\Modules\Audit\Livewire;

use App\Modules\Audit\Enums\AuditTab;
use App\Modules\Audit\Models\SensitiveDataAccess;
use App\Modules\Organization\Contracts\AppSettings;
use App\Modules\Shared\Enums\AuditLogName;
use App\Modules\Shared\Enums\Permission;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

/** Consulta de solo lectura de la auditoría: cambios, eventos de seguridad y accesos a datos sensibles. */
#[Layout('components.layouts.backoffice')]
final class AuditLogIndex extends Component
{
    use WithPagination;

    private const DAY_PATTERN = '/^\d{4}-\d{2}-\d{2}$/';

    #[Url(except: 'changes')]
    public string $tab = 'changes';

    #[Url(except: '')]
    public string $logName = '';

    #[Url(except: '')]
    public string $from = '';

    #[Url(except: '')]
    public string $to = '';

    public function mount(): void
    {
        Gate::authorize(Permission::AuditView->value);
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $tab = AuditTab::tryFrom($this->tab) ?? AuditTab::Changes;

        return view('audit::livewire.audit-log-index', [
            'currentTab' => $tab,
            'tabs' => AuditTab::cases(),
            'logNames' => array_filter(AuditLogName::cases(), static fn(AuditLogName $name): bool => $name !== AuditLogName::Security),
            'entries' => $tab === AuditTab::SensitiveAccess ? $this->sensitiveAccesses() : $this->activities($tab),
            'timezone' => app(AppSettings::class)->agencyTimezone(),
        ])->title(__('audit.title'))
            ->layoutData(['heading' => __('audit.title')]);
    }

    /** @return LengthAwarePaginator<int, Activity> */
    private function activities(AuditTab $tab): LengthAwarePaginator
    {
        $logName = $tab === AuditTab::Security ? AuditLogName::Security->value : (AuditLogName::tryFrom($this->logName)?->value);

        return Activity::query()
            ->with('causer')
            ->when($tab === AuditTab::Changes, static fn(Builder $query) => $query->where('log_name', '!=', AuditLogName::Security->value))
            ->when($logName !== null, static fn(Builder $query) => $query->where('log_name', $logName))
            ->when($this->fromDate(), static fn(Builder $query, CarbonImmutable $from) => $query->where('created_at', '>=', $from))
            ->when($this->toDate(), static fn(Builder $query, CarbonImmutable $to) => $query->where('created_at', '<', $to))
            ->latest('id')
            ->paginate(config()->integer('travel.audit.per_page'));
    }

    /** @return LengthAwarePaginator<int, SensitiveDataAccess> */
    private function sensitiveAccesses(): LengthAwarePaginator
    {
        return SensitiveDataAccess::query()
            ->when($this->fromDate(), static fn(Builder $query, CarbonImmutable $from) => $query->where('accessed_at', '>=', $from))
            ->when($this->toDate(), static fn(Builder $query, CarbonImmutable $to) => $query->where('accessed_at', '<', $to))
            ->latest('id')
            ->paginate(config()->integer('travel.audit.per_page'));
    }

    /** Las fechas del filtro son días en la zona de la agencia; la BD guarda UTC. */
    private function fromDate(): ?CarbonImmutable
    {
        return $this->parseDay($this->from);
    }

    private function toDate(): ?CarbonImmutable
    {
        return $this->parseDay($this->to)?->addDay();
    }

    private function parseDay(string $value): ?CarbonImmutable
    {
        if (preg_match(self::DAY_PATTERN, $value) !== 1) {
            return null;
        }

        return CarbonImmutable::parse($value, app(AppSettings::class)->agencyTimezone())->utc();
    }
}
