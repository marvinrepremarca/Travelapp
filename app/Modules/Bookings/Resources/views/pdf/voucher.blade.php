@extends('documents::layout')

@section('content')
    <p><span class="badge">{{ __('bookings.item_status.confirmed') }}</span></p>
    <table class="items">
        <tr><th>{{ __('bookings.pdf.booking') }}</th><td>{{ $booking->number }} · {{ $booking->customer->display_name }}</td></tr>
        <tr><th>{{ __('bookings.pdf.service') }}</th><td>{{ $item->description }} · {{ $item->product_type->label() }}</td></tr>
        <tr><th>{{ __('bookings.pdf.date') }}</th><td>
            {{ $item->service_date->locale(app()->getLocale())->isoFormat('dddd LL') }}
            @if ($item->nights > 0) — {{ __('quotes.itinerary.until', ['nights' => $item->nights, 'date' => $item->service_date->addDays($item->nights)->locale(app()->getLocale())->isoFormat('ll')]) }} @endif
        </td></tr>
        <tr><th>{{ __('bookings.action_fields.confirmation') }}</th><td><strong>{{ $item->supplier_confirmation }}</strong></td></tr>
        <tr><th>{{ __('bookings.pdf.passengers') }}</th><td>
            @forelse ($item->passengers as $passenger)
                {{ $passenger->traveler->fullName() }} ({{ $passenger->passenger_type->label() }})<br>
            @empty
                <span class="warning">{{ __('bookings.passengers.missing') }}</span>
            @endforelse
        </td></tr>
    </table>
    <div class="box subtle">{{ __('bookings.pdf.voucher_note') }}</div>
@endsection
