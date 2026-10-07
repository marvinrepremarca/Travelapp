@extends('documents::layout')

@section('content')
    <p><strong>{{ $departure->productName }}</strong> · {{ $departure->destinationCity }}</p>
    <p>{{ $departure->startsAtLocal()->locale(app()->getLocale())->isoFormat('LLLL') }} ({{ $departure->timezone }})</p>
    <p class="subtle">
        {{ __('operations.departures.guide') }}: {{ $assignment?->guide?->name ?? __('operations.departures.no_guide') }}
        @if ($assignment?->guide) · {{ $assignment->guide->phone }} @endif
        · {{ __('operations.departures.vehicle') }}: {{ $assignment?->vehicle?->plate ?? __('operations.departures.no_vehicle') }}
    </p>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>{{ __('operations.manifest.traveler') }}</th>
                <th>{{ __('operations.manifest.booking') }}</th>
                <th>{{ __('operations.manifest.age') }}</th>
                <th>{{ __('operations.manifest.nationality') }}</th>
                <th>{{ __('operations.manifest.document') }}</th>
                <th>{{ __('operations.manifest.contact') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($passengers as $passenger)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $passenger->travelerName }}</td>
                    <td>{{ $passenger->bookingNumber }}</td>
                    <td>{{ $passenger->age }}</td>
                    <td>{{ $passenger->nationality }}</td>
                    <td>{{ $passenger->maskedDocument ?: '—' }}</td>
                    <td>{{ $passenger->contactPhone ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="7">{{ __('operations.manifest.empty') }}</td></tr>
            @endforelse
        </tbody>
    </table>
    <p class="subtle">{{ __('operations.manifest.total', ['count' => count($passengers)]) }}</p>
@endsection
