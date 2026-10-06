<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Livewire;

use App\Modules\Identity\Models\User;
use App\Modules\Invoicing\Models\Invoice;
use App\Modules\Shared\Enums\Permission;
use App\Modules\Shared\Money\MoneyPresenter;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Detalle de una factura o nota: cliente, líneas por naturaleza (tercero / propio), IVA y estado electrónico. */
#[Layout('components.layouts.backoffice')]
final class InvoiceShow extends Component
{
    #[Locked]
    public string $invoiceUlid = '';

    public function mount(Invoice $invoice): void
    {
        /** @var User $user */
        $user = Auth::user();
        abort_unless($user->can(Permission::FinanceAccess->value), 403);
        abort_unless($invoice->isVisibleTo($user), 404);
        $this->invoiceUlid = $invoice->ulid;
    }

    public function render(MoneyPresenter $presenter): View
    {
        $invoice = Invoice::query()->with(['lines', 'related:id,ulid,number'])->where('ulid', $this->invoiceUlid)->firstOrFail();
        $title = __('invoicing.show_title', ['type' => $invoice->type->label(), 'number' => $invoice->number]);

        return view('invoicing::livewire.invoice-show', [
            'invoice' => $invoice,
            'presenter' => $presenter,
            'timezone' => config()->string('travel.agency.timezone'),
        ])->title($title)->layoutData(['heading' => $title]);
    }
}
