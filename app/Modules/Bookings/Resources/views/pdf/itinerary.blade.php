@extends('documents::layout')

@section('content')
    <p>{{ __('quotes.pdf.prepared_for', ['customer' => $booking->customer->display_name]) }} · {{ $booking->title }}</p>

    @forelse ($days as $date => $items)
        <div class="day">
            <h2>{{ __('bookings.pdf.day', ['day' => $loop->iteration, 'date' => \Carbon\CarbonImmutable::parse($date)->locale(app()->getLocale())->isoFormat('dddd LL')]) }}</h2>
            @foreach ($items as $item)
                <h3>{{ $item->description }}</h3>
                <p class="subtle">
                    {{ $item->product_type->label() }} · {{ $item->status->label() }}
                    @if ($item->supplier_confirmation) · {{ __('bookings.items.confirmation', ['code' => $item->supplier_confirmation]) }} @endif
                    @if ($item->nights > 0) · {{ __('quotes.itinerary.until', ['nights' => $item->nights, 'date' => $item->service_date->addDays($item->nights)->locale(app()->getLocale())->isoFormat('ll')]) }} @endif
                </p>
                @if ($item->passengers->isNotEmpty())
                    <p>{{ __('bookings.pdf.passengers') }}: {{ $item->passengers->map(fn ($passenger) => $passenger->traveler->fullName())->implode(', ') }}</p>
                @endif
            @endforeach
        </div>
    @empty
        <p>{{ __('bookings.pdf.empty') }}</p>
    @endforelse
@endsection
