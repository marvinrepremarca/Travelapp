<x-layouts.portal>
    <div class="mx-auto flex max-w-page flex-col gap-lg">
        <x-ui.alert :tone="\App\Modules\Shared\Enums\Tone::Info">{{ __('integrations.fake_checkout.notice') }}</x-ui.alert>
        <x-ui.card :title="__('integrations.fake_checkout.title')">
            <div class="flex flex-col gap-md">
                <p>{{ __('integrations.fake_checkout.amount', ['amount' => app(\App\Modules\Shared\Money\MoneyPresenter::class)->format($amount)]) }}</p>
                <p class="text-caption text-text-subtle">{{ __('integrations.fake_checkout.reference', ['reference' => $reference]) }}</p>
                @if ($completed)
                    <x-ui.alert :tone="$completed->tone()" role="status">{{ __('integrations.fake_checkout.done', ['status' => $completed->label()]) }}</x-ui.alert>
                @else
                    <form method="POST" action="{{ \Illuminate\Support\Facades\URL::signedRoute('integrations.fake-checkout.complete') }}" class="flex flex-wrap gap-sm">
                        @csrf
                        <input type="hidden" name="reference" value="{{ $reference }}">
                        <input type="hidden" name="amount" value="{{ $amount->getMinorAmount()->toInt() }}">
                        <input type="hidden" name="currency" value="{{ $amount->getCurrency()->getCurrencyCode() }}">
                        <x-ui.button type="submit" name="outcome" value="{{ \App\Modules\Payments\Enums\PaymentStatus::Approved->value }}">{{ __('integrations.fake_checkout.approve') }}</x-ui.button>
                        <x-ui.button type="submit" variant="secondary" name="outcome" value="{{ \App\Modules\Payments\Enums\PaymentStatus::Rejected->value }}">{{ __('integrations.fake_checkout.reject') }}</x-ui.button>
                    </form>
                @endif
            </div>
        </x-ui.card>
    </div>
</x-layouts.portal>
