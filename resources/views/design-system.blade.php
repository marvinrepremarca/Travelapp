@php
    use App\Modules\Shared\Enums\Tone;
@endphp
<x-layouts.backoffice :heading="__('design_system.title')" :title="__('design_system.title')">
    <div class="flex flex-col gap-section">
        <x-ui.card :title="__('design_system.buttons')">
            <div class="flex flex-wrap gap-sm">
                <x-ui.button>{{ __('design_system.primary') }}</x-ui.button>
                <x-ui.button variant="secondary">{{ __('design_system.secondary') }}</x-ui.button>
                <x-ui.button variant="ghost">{{ __('design_system.ghost') }}</x-ui.button>
                <x-ui.button variant="danger">{{ __('design_system.danger') }}</x-ui.button>
                <x-ui.button disabled>{{ __('design_system.disabled') }}</x-ui.button>
            </div>
        </x-ui.card>

        <x-ui.card :title="__('design_system.badges_and_alerts')">
            <div class="flex flex-col gap-md">
                <div class="flex flex-wrap gap-sm">
                    @foreach (Tone::cases() as $tone)
                        <x-ui.badge :tone="$tone">{{ __("design_system.tones.{$tone->value}") }}</x-ui.badge>
                    @endforeach
                </div>
                @foreach ([Tone::Info, Tone::Success, Tone::Warning, Tone::Danger] as $tone)
                    <x-ui.alert :tone="$tone">{{ __("design_system.tones.{$tone->value}") }}</x-ui.alert>
                @endforeach
            </div>
        </x-ui.card>

        <x-ui.card :title="__('design_system.form')">
            <form class="flex max-w-form flex-col gap-md" novalidate>
                <x-ui.field :label="__('design_system.sample_field')" for="sample" :hint="__('design_system.sample_hint')">
                    <x-ui.input name="sample" hint />
                </x-ui.field>
                <x-ui.field :label="__('design_system.sample_error_field')" for="sample_error" :error="__('design_system.sample_error')">
                    <x-ui.input name="sample_error" aria-invalid="true" aria-describedby="sample_error-error" />
                </x-ui.field>
            </form>
        </x-ui.card>

        <x-ui.card :title="__('design_system.table')">
            <x-ui.table :caption="__('design_system.table_caption')">
                <x-slot:head>
                    <tr>
                        <th scope="col" class="px-md py-sm">{{ __('design_system.column_product') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('design_system.column_channel') }}</th>
                    </tr>
                </x-slot:head>
                @foreach (\App\Modules\Shared\Enums\ProductType::cases() as $product)
                    <tr wire:key="product-{{ $product->value }}">
                        <td class="px-md py-sm">{{ $product->label() }}</td>
                        <td class="px-md py-sm">{{ \App\Modules\Shared\Enums\SalesChannel::Branch->label() }}</td>
                    </tr>
                @endforeach
            </x-ui.table>
        </x-ui.card>

        <x-ui.card :title="__('design_system.states')">
            <div class="grid gap-md md:grid-cols-2">
                <x-ui.empty-state :title="__('design_system.empty_title')" :description="__('design_system.empty_description')" />
                <x-ui.skeleton :lines="4" />
            </div>
        </x-ui.card>
    </div>
</x-layouts.backoffice>
