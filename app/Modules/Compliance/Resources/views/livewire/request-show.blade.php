@php use App\Modules\Shared\Enums\Tone; use App\Modules\Shared\Support\Mask; @endphp
<div class="flex flex-col gap-lg">
    @if (session('status'))<x-ui.alert :tone="Tone::Success" role="status">{{ session('status') }}</x-ui.alert>@endif

    <x-ui.card>
        <dl class="grid gap-md md:grid-cols-4">
            <div><dt class="text-caption text-text-subtle">{{ __('compliance.requests.fields.type') }}</dt><dd class="font-medium">{{ $request->type->label() }}</dd></div>
            <div><dt class="text-caption text-text-subtle">{{ __('compliance.requests.status') }}</dt><dd><x-ui.badge :tone="$request->status->tone()">{{ $request->status->label() }}</x-ui.badge></dd></div>
            <div><dt class="text-caption text-text-subtle">{{ __('compliance.requests.received_at') }}</dt><dd>{{ $request->received_at->timezone(config('travel.agency.timezone'))->locale(app()->getLocale())->isoFormat('lll') }}</dd></div>
            <div>
                <dt class="text-caption text-text-subtle">{{ __('compliance.requests.due_on') }}</dt>
                <dd @class(['font-medium', 'text-danger' => $request->status->isOpen() && $request->due_on->lt($today)])>
                    {{ $request->due_on->locale(app()->getLocale())->isoFormat('LL') }}
                    @if ($request->status->isOpen())
                        <span class="text-caption">· {{ $request->due_on->lt($today) ? __('compliance.requests.overdue') : trans_choice('compliance.requests.days_left', (int) $today->diffInDays($request->due_on), ['days' => (int) $today->diffInDays($request->due_on)]) }}</span>
                    @endif
                </dd>
            </div>
            <div><dt class="text-caption text-text-subtle">{{ __('compliance.requests.fields.requester_name') }}</dt><dd>{{ $revealed ? $request->requester_name : Mask::value($request->requester_name) }}</dd></div>
            <div><dt class="text-caption text-text-subtle">{{ __('compliance.requests.fields.document_number') }}</dt><dd>{{ $revealed ? $request->document_number : Mask::value($request->document_number) }}</dd></div>
            <div><dt class="text-caption text-text-subtle">{{ __('compliance.requests.fields.email') }}</dt><dd>{{ $request->email ? ($revealed ? $request->email : Mask::value($request->email)) : '—' }}</dd></div>
            <div><dt class="text-caption text-text-subtle">{{ __('compliance.requests.fields.phone') }}</dt><dd>{{ $request->phone ? ($revealed ? $request->phone : Mask::value($request->phone)) : '—' }}</dd></div>
        </dl>
        @unless ($revealed)
            <div class="mt-md"><x-ui.button type="button" variant="secondary" wire:click="reveal" wire:loading.attr="disabled" wire:target="reveal">{{ __('compliance.requests.reveal') }}</x-ui.button></div>
        @endunless
        <div class="mt-md">
            <p class="text-caption text-text-subtle">{{ __('compliance.requests.fields.details') }}</p>
            <p>{{ $revealed ? $request->details : __('compliance.requests.details_hidden') }}</p>
        </div>
        <p class="mt-md text-caption text-text-subtle">{{ __('compliance.requests.handled_by', ['name' => $request->handler->name, 'channel' => $request->channel->label()]) }}</p>
    </x-ui.card>

    @if ($request->status->isOpen())
        <x-ui.card :title="__('compliance.requests.answer_title')">
            <form wire:submit="resolve" class="flex flex-col gap-md" novalidate>
                <x-ui.field :label="__('compliance.requests.status')" for="status"><x-ui.select name="status" wire:model="status" :options="$statuses" required /></x-ui.field>
                <x-ui.field :label="__('compliance.requests.response')" for="response" :hint="__('compliance.requests.response_hint')"><x-ui.input name="response" wire:model="response" hint /></x-ui.field>
                <div><x-ui.button type="submit" wire:loading.attr="disabled" wire:target="resolve">{{ __('compliance.requests.save_answer') }}</x-ui.button></div>
            </form>
        </x-ui.card>
    @else
        <x-ui.card :title="__('compliance.requests.response')">
            <p>{{ $request->response }}</p>
            <p class="mt-sm text-caption text-text-subtle">{{ __('compliance.requests.resolved_at', ['date' => $request->resolved_at?->timezone(config('travel.agency.timezone'))->locale(app()->getLocale())->isoFormat('lll')]) }}</p>
        </x-ui.card>
    @endif

    <div><x-ui.link-button variant="secondary" :href="route('compliance.requests')">{{ __('compliance.requests.back') }}</x-ui.link-button></div>
</div>
