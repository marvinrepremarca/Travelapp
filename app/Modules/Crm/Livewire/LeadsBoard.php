<?php

declare(strict_types=1);

namespace App\Modules\Crm\Livewire;

use App\Modules\Crm\Enums\LeadStatus;
use App\Modules\Crm\Models\Lead;
use App\Modules\Identity\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/** Embudo tipo kanban: una columna por etapa abierta (cada una crece con "ver más") y totales de ganados y perdidos, dentro del alcance. */
#[Layout('components.layouts.backoffice')]
final class LeadsBoard extends Component
{
    #[Url(except: '')]
    public string $search = '';

    /** @var array<string, int> páginas visibles por etapa */
    public array $pages = [];

    public function showMore(string $status): void
    {
        $stage = LeadStatus::tryFrom($status);
        if ($stage instanceof LeadStatus) {
            $this->pages[$stage->value] = ($this->pages[$stage->value] ?? 1) + 1;
        }
    }

    public function updatedSearch(): void
    {
        $this->reset('pages');
    }

    public function render(): View
    {
        $base = fn(): Builder => Lead::query()
            ->visibleTo($this->actor())
            ->when($this->search !== '', fn(Builder $query) => $query->where(fn(Builder $inner) => $inner
                ->where('contact_name', 'like', '%' . $this->search . '%')
                ->orWhere('destination', 'like', '%' . $this->search . '%')));

        // Un solo conteo agrupado por etapa en lugar de una consulta por columna.
        $totals = $base()->toBase()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(static fn(mixed $count): int => (int) $count);
        $columns = [];

        foreach ([LeadStatus::New, LeadStatus::Contacted, LeadStatus::Quoted] as $status) {
            $columns[] = [
                'status' => $status,
                'leads' => $base()->where('status', $status)->latest('status_changed_at')->limit(config()->integer('travel.crm.board_column_size') * ($this->pages[$status->value] ?? 1))->get(),
                'total' => $totals->get($status->value, 0),
            ];
        }

        return view('crm::livewire.leads-board', [
            'columns' => $columns,
            'won' => $totals->get(LeadStatus::Won->value, 0),
            'lost' => $totals->get(LeadStatus::Lost->value, 0),
        ])->title(__('crm.leads.title'))
            ->layoutData(['heading' => __('crm.leads.title')]);
    }

    private function actor(): User
    {
        /** @var User */
        return Auth::user();
    }
}
