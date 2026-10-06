<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Livewire;

use App\Modules\Bookings\Contracts\BookingInvoicing;
use App\Modules\Bookings\Data\InvoiceCandidate;
use App\Modules\Identity\Models\User;
use App\Modules\Invoicing\Actions\IssueBookingInvoiceAction;
use App\Modules\Invoicing\Enums\InvoiceType;
use App\Modules\Invoicing\Models\Invoice;
use App\Modules\Payments\Contracts\BookingCollections;
use App\Modules\Shared\Enums\Permission;
use App\Modules\Shared\Exceptions\BusinessRuleException;
use App\Modules\Shared\Money\MoneyPresenter;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Facturación: expedientes listos para facturar (confirmados y pagados en su totalidad) y documentos emitidos.
 * Solo finanzas emite.
 */
#[Layout('components.layouts.backoffice')]
final class InvoicesIndex extends Component
{
    use WithPagination;

    public const TAB_READY = 'ready';

    public const TAB_ISSUED = 'issued';

    #[Url(except: self::TAB_READY)]
    public string $tab = self::TAB_READY;

    #[Url(except: '')]
    public string $type = '';

    #[Url(except: '')]
    public string $search = '';

    public function mount(): void
    {
        $this->authorizeFinance();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['tab', 'type', 'search'], true)) {
            $this->resetPage();
        }
    }

    public function issue(string $bookingUlid, IssueBookingInvoiceAction $issue): void
    {
        $this->authorizeFinance();

        try {
            $invoice = $issue->execute($this->actor(), $bookingUlid, CarbonImmutable::now());
        } catch (BusinessRuleException $violation) {
            $this->addError('issue', $violation->getMessage());

            return;
        }

        session()->flash('status', __('invoicing.issued', ['number' => $invoice->number]));
        $this->redirectRoute('invoicing.show', $invoice, navigate: true);
    }

    public function render(MoneyPresenter $presenter): View
    {
        $data = [
            'presenter' => $presenter,
            'types' => InvoiceType::cases(),
            'ready' => $this->tab === self::TAB_READY ? $this->ready() : [],
            'invoices' => $this->tab === self::TAB_ISSUED ? $this->issued() : null,
        ];

        return view('invoicing::livewire.invoices-index', $data)
            ->title(__('invoicing.title'))
            ->layoutData(['heading' => __('invoicing.title')]);
    }

    /** @return list<InvoiceCandidate> */
    private function ready(): array
    {
        $candidates = app(BookingInvoicing::class)->confirmed($this->actor(), config()->integer('travel.invoicing.ready_candidates_limit'));
        if ($candidates === []) {
            return [];
        }

        $ulids = array_map(static fn(InvoiceCandidate $candidate): string => $candidate->ulid, $candidates);
        $invoiced = Invoice::query()->whereIn('booking_invoice_key', $ulids)->pluck('booking_invoice_key')->flip();
        $collected = app(BookingCollections::class)->netCollected(array_combine($ulids, array_map(static fn(InvoiceCandidate $candidate): string => $candidate->total->getCurrency()->getCurrencyCode(), $candidates)));

        return array_values(array_filter(
            $candidates,
            static fn(InvoiceCandidate $candidate): bool => ! $invoiced->has($candidate->ulid)
                && ! $candidate->total->isZero()
                && isset($collected[$candidate->ulid])
                && ! $candidate->total->isGreaterThan($collected[$candidate->ulid]),
        ));
    }

    /** @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, Invoice> */
    private function issued(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $type = InvoiceType::tryFrom($this->type);

        return Invoice::query()
            ->visibleTo($this->actor())
            ->when($type instanceof InvoiceType, static fn(Builder $query) => $query->where('type', $type))
            ->when($this->search !== '', fn(Builder $query) => $query->where(fn(Builder $inner) => $inner
                ->where('number', 'like', '%' . $this->search . '%')
                ->orWhere('booking_number', 'like', '%' . $this->search . '%')
                ->orWhere('customer_name', 'like', '%' . $this->search . '%')))
            ->latest('issued_at')
            ->latest('id')
            ->paginate(config()->integer('travel.invoicing.per_page'), ['id', 'ulid', 'type', 'number', 'booking_ulid', 'booking_number', 'customer_name', 'currency', 'total_minor', 'e_invoice_status', 'issued_at']);
    }

    private function authorizeFinance(): void
    {
        abort_unless($this->actor()->can(Permission::FinanceAccess->value), 403);
    }

    private function actor(): User
    {
        /** @var User */
        return Auth::user();
    }
}
