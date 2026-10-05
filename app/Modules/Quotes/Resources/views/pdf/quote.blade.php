@extends('documents::layout')

@section('content')
    <p>{{ __('quotes.pdf.prepared_for', ['customer' => $quote->customer->display_name]) }}</p>
    <p class="subtle">
        {{ __('quotes.public.subtitle', ['number' => $quote->number, 'version' => $version->version]) }} ·
        {{ __('quotes.validity', ['date' => $version->valid_until->timezone($timezone)->locale(app()->getLocale())->isoFormat('lll')]) }}
    </p>

    @foreach ($options as $option)
        <h2>{{ __('quotes.options.label', ['label' => $option['label']]) }} · {{ $option['title'] }}</h2>

        <table class="items">
            <thead>
                <tr>
                    <th>{{ __('quotes.item_fields.description') }}</th>
                    <th>{{ __('quotes.item_fields.service_date') }}</th>
                    <th class="right">{{ __('quotes.pdf.price') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($option['lines'] as $line)
                    <tr>
                        <td>
                            {{ $line['description'] }}
                            <div class="subtle">{{ \App\Modules\Shared\Enums\ProductType::from($line['product_type'])->label() }} · {{ __('quotes.pdf.passengers', ['count' => count($line['passenger_ages'])]) }}@if ($line['nights'] > 0) · {{ __('quotes.pdf.nights', ['count' => $line['nights']]) }}@endif</div>
                        </td>
                        <td>{{ \Carbon\CarbonImmutable::parse($line['service_date'])->locale(app()->getLocale())->isoFormat('ll') }}</td>
                        <td class="right">{{ app(\App\Modules\Shared\Money\MoneyPresenter::class)->format($line['sale']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p class="total">{{ __('quotes.options.total') }}: {{ app(\App\Modules\Shared\Money\MoneyPresenter::class)->format($option['total']) }}</p>

        <h3>{{ __('quotes.itinerary.title') }}</h3>
        @foreach ($option['itinerary'] as $day)
            <div class="day">
                <p class="day-title">{{ __('quotes.itinerary.day', ['day' => $day['day'], 'date' => $day['date']->locale(app()->getLocale())->isoFormat('dddd LL')]) }}</p>
                @foreach ($day['entries'] as $entry)
                    <p>· {{ $entry['description'] }}@if ($entry['ends_on']) — {{ __('quotes.itinerary.until', ['nights' => $entry['nights'], 'date' => $entry['ends_on']->locale(app()->getLocale())->isoFormat('ll')]) }}@endif</p>
                @endforeach
            </div>
        @endforeach
    @endforeach

    <div class="box subtle">{{ __('quotes.pdf.disclaimer') }}</div>
@endsection
