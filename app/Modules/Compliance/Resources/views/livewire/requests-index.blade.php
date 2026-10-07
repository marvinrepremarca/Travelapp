@php use App\Modules\Shared\Enums\Tone; use App\Modules\Shared\Support\Mask; @endphp
<div class="flex flex-col gap-lg">
    @if (session('status'))<x-ui.alert :tone="Tone::Success" role="status">{{ session('status') }}</x-ui.alert>@endif
    <div class="flex flex-wrap items-center justify-between gap-sm">
        <label class="flex items-center gap-sm"><input type="checkbox" wire:model.live="onlyOpen"> {{ __('compliance.requests.only_open') }}</label>
        <div class="flex flex-wrap gap-sm">
            <x-ui.link-button variant="secondary" :href="route('compliance.index')">{{ __('compliance.back') }}</x-ui.link-button>
            <x-ui.link-button :href="route('compliance.requests.create')">{{ __('compliance.requests.create') }}</x-ui.link-button>
        </div>
    </div>
    <p class="text-caption text-text-subtle">{{ __('compliance.requests.legal_hint', ['days' => config('travel.compliance.request_business_days')]) }}</p>

    <div wire:loading.delay wire:target="onlyOpen"><x-ui.skeleton :lines="4" /></div>
    <div wire:loading.remove wire:target="onlyOpen">
        @if ($requests->isEmpty())
            <x-ui.empty-state :title="__('compliance.requests.empty')" />
        @else
            <x-ui.table :caption="__('compliance.requests.title')">
                <x-slot:head>
                    <tr>
                        <th scope="col" class="px-md py-sm">{{ __('compliance.requests.number') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('compliance.requests.fields.type') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('compliance.requests.fields.requester_name') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('compliance.requests.due_on') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('compliance.requests.status') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('compliance.requests.handler') }}</th>
                    </tr>
                </x-slot:head>
                @foreach ($requests as $request)
                    <tr wire:key="request-{{ $request->ulid }}">
                        <th scope="row" class="px-md py-sm text-left font-medium"><a href="{{ route('compliance.requests.show', $request) }}" wire:navigate class="text-brand underline">{{ $request->number }}</a></th>
                        <td class="px-md py-sm">{{ $request->type->label() }}</td>
                        <td class="px-md py-sm">{{ Mask::value($request->requester_name) }}</td>
                        <td class="px-md py-sm">
                            {{ $request->due_on->locale(app()->getLocale())->isoFormat('ll') }}
                            @if ($request->status->isOpen() && $request->due_on->lt($today))
                                <x-ui.badge :tone="Tone::Danger">{{ __('compliance.requests.overdue') }}</x-ui.badge>
                            @elseif ($request->status->isOpen() && $request->due_on->lte($alertLimit))
                                <x-ui.badge :tone="Tone::Warning">{{ __('compliance.requests.due_soon') }}</x-ui.badge>
                            @endif
                        </td>
                        <td class="px-md py-sm"><x-ui.badge :tone="$request->status->tone()">{{ $request->status->label() }}</x-ui.badge></td>
                        <td class="px-md py-sm">{{ $request->handler->name }}</td>
                    </tr>
                @endforeach
            </x-ui.table>
            {{ $requests->links() }}
        @endif
    </div>
</div>
