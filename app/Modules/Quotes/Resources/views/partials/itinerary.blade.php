{{-- Itinerario día a día: $days viene de ItineraryBuilder. --}}
<ol class="flex flex-col gap-md">
    @foreach ($days as $day)
        <li wire:key="day-{{ $day['date']->toDateString() }}">
            <p class="font-medium">{{ __('quotes.itinerary.day', ['day' => $day['day'], 'date' => $day['date']->locale(app()->getLocale())->isoFormat('dddd LL')]) }}</p>
            <ul class="ml-md flex flex-col gap-xs">
                @foreach ($day['entries'] as $entry)
                    <li>
                        {{ $entry['description'] }} <span class="text-caption text-text-subtle">· {{ $entry['type']->label() }}</span>
                        @if ($entry['ends_on'])
                            <span class="text-caption text-text-subtle">· {{ __('quotes.itinerary.until', ['nights' => $entry['nights'], 'date' => $entry['ends_on']->locale(app()->getLocale())->isoFormat('ll')]) }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </li>
    @endforeach
</ol>
