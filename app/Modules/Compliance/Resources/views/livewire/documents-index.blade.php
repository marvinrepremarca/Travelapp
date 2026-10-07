@php use App\Modules\Shared\Enums\Tone; @endphp
<div class="flex flex-col gap-lg">
    @if (session('status'))<x-ui.alert :tone="Tone::Success" role="status">{{ session('status') }}</x-ui.alert>@endif
    <div class="flex flex-wrap justify-between gap-sm">
        <x-ui.link-button variant="secondary" :href="route('compliance.index')">{{ __('compliance.back') }}</x-ui.link-button>
        <x-ui.link-button :href="route('compliance.documents.create')">{{ __('compliance.documents.create') }}</x-ui.link-button>
    </div>

    @if ($documents->isEmpty())
        <x-ui.empty-state :title="__('compliance.documents.empty')" :description="__('compliance.documents.empty_hint')" />
    @else
        <x-ui.table :caption="__('compliance.documents.title')">
            <x-slot:head>
                <tr>
                    <th scope="col" class="px-md py-sm">{{ __('compliance.documents.fields.type') }}</th>
                    <th scope="col" class="px-md py-sm">{{ __('compliance.documents.fields.number') }}</th>
                    <th scope="col" class="px-md py-sm">{{ __('compliance.documents.fields.expires_on') }}</th>
                    <th scope="col" class="px-md py-sm">{{ __('compliance.documents.fields.responsible_id') }}</th>
                    <th scope="col" class="px-md py-sm"><span class="sr-only">{{ __('shared.actions') }}</span></th>
                </tr>
            </x-slot:head>
            @foreach ($documents as $document)
                <tr wire:key="document-{{ $document->ulid }}">
                    <th scope="row" class="px-md py-sm text-left font-medium">{{ $document->type->label() }}</th>
                    <td class="px-md py-sm">{{ $document->number }} @if ($document->issuer)<span class="text-caption text-text-subtle">· {{ $document->issuer }}</span>@endif</td>
                    <td class="px-md py-sm">
                        {{ $document->expires_on->locale(app()->getLocale())->isoFormat('ll') }}
                        @if ($document->expires_on->lt($today))
                            <x-ui.badge :tone="Tone::Danger">{{ __('compliance.documents.expired') }}</x-ui.badge>
                        @elseif ($document->expires_on->lte($alertLimit))
                            <x-ui.badge :tone="Tone::Warning">{{ trans_choice('compliance.documents.expires_in', (int) $today->diffInDays($document->expires_on), ['days' => (int) $today->diffInDays($document->expires_on)]) }}</x-ui.badge>
                        @else
                            <x-ui.badge :tone="Tone::Success">{{ __('compliance.documents.valid') }}</x-ui.badge>
                        @endif
                    </td>
                    <td class="px-md py-sm">{{ $document->responsible->name }}</td>
                    <td class="px-md py-sm"><a href="{{ route('compliance.documents.edit', $document) }}" wire:navigate class="text-brand underline">{{ __('shared.edit') }}<span class="sr-only"> {{ $document->number }}</span></a></td>
                </tr>
            @endforeach
        </x-ui.table>
        {{ $documents->links() }}
    @endif
</div>
