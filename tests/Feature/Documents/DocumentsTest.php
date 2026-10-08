<?php

declare(strict_types=1);

use App\Modules\Bookings\Actions\AssignPassengersAction;
use App\Modules\Bookings\Actions\ChangeItemStatusAction;
use App\Modules\Bookings\Actions\ConfirmItemAction;
use App\Modules\Bookings\Enums\BookingItemStatus;
use App\Modules\Bookings\Services\BookingDocuments;
use App\Modules\Customers\Models\Traveler;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Organization\Contracts\AgencyLetterhead;
use App\Modules\Organization\Models\AgencyProfile;
use App\Modules\Pricing\Models\MarkupRule;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Models\QuoteVersion;
use App\Modules\Quotes\Services\QuoteDocument;
use App\Modules\Quotes\Services\QuoteLinks;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-01 15:00:00'));
    MarkupRule::factory()->percentage(1000)->create();
});

function pdfQuote(): Quote
{
    $booking = familyBooking(agent());

    return Quote::query()->where('ulid', $booking->quote_ulid)->sole();
}

it('renders the sent quote version without net prices or margins', function (): void {
    $quote = pdfQuote();
    $html = app(QuoteDocument::class)->html($quote, QuoteVersion::query()->where('quote_id', $quote->id)->sole());

    expect($html)->toContain('Hotel Caribe')
        ->toContain(__('quotes.itinerary.title'))
        ->toContain(__('quotes.pdf.prepared_for', ['customer' => $quote->customer->display_name]))
        ->not->toContain(__('quotes.options.margin'))
        ->not->toContain(__('quotes.item_fields.net_amount'));
});

it('downloads the quote PDF only within the agent scope', function (): void {
    $quote = pdfQuote();
    $owner = User::query()->findOrFail($quote->owner_id);

    $response = actingAs($owner)->get(route('quotes.pdf', [$quote, 1]));
    $response->assertOk()->assertHeader('content-type', 'application/pdf');
    expect((string) $response->getContent())->toStartWith('%PDF');

    actingAs(agent())->get(route('quotes.pdf', [$quote, 1]))->assertNotFound();
    actingAs($owner)->get(route('quotes.pdf', [$quote, 9]))->assertNotFound();
});

it('lets the customer download the PDF only with a valid signature', function (): void {
    $quote = pdfQuote();
    // La cotización ya está aceptada: el enlace sigue valiendo hasta el fin de la vigencia.
    $url = (string) app(QuoteLinks::class)->customerPdfUrl($quote, 1);

    get($url)->assertOk()->assertHeader('content-type', 'application/pdf');
    get(route(QuoteLinks::PDF_ROUTE, ['quote' => $quote->ulid, 'version' => 1]))->assertForbidden();
    expect(app(QuoteLinks::class)->customerPdfUrl(Quote::factory()->create(), 1))->toBeNull();
});

it('issues vouchers only for confirmed services within the scope', function (): void {
    $agent = agent();
    $booking = familyBooking($agent);
    $hotel = hotelOf($booking);

    actingAs($agent)->get(route('bookings.voucher', [$booking, $hotel->ulid]))->assertNotFound();

    app(ConfirmItemAction::class)->execute($hotel, 'HCR-77', null, CarbonImmutable::now());
    actingAs($agent)->get(route('bookings.voucher', [$booking, $hotel->ulid]))->assertOk()->assertHeader('content-type', 'application/pdf');
    actingAs(agent())->get(route('bookings.voucher', [$booking, $hotel->ulid]))->assertNotFound();
    actingAs($agent)->get(route('bookings.voucher', [familyBooking($agent), $hotel->ulid]))->assertNotFound();
});

it('puts the confirmation code and passengers on the voucher', function (): void {
    $booking = familyBooking(agent());
    $hotel = hotelOf($booking);
    $mother = Traveler::factory()->create(['customer_id' => $booking->customer_id, 'first_name' => 'Laura', 'last_name' => 'Pérez', 'birth_date' => '1986-05-01']);
    $son = Traveler::factory()->create(['customer_id' => $booking->customer_id, 'first_name' => 'Tomás', 'last_name' => 'Pérez', 'birth_date' => '2018-03-15']);
    app(AssignPassengersAction::class)->execute($hotel, [$mother->ulid, $son->ulid]);
    app(ConfirmItemAction::class)->execute($hotel, 'HCR-77', null, CarbonImmutable::now());

    $html = app(BookingDocuments::class)->voucherHtml($hotel->fresh() ?? $hotel);

    expect($html)->toContain('HCR-77')->toContain('Laura Pérez')->toContain('Tomás Pérez')
        ->not->toContain((string) $mother->passport_number ?: 'PASSPORT-NOT-SET');
});

it('builds the traveler itinerary with active services only', function (): void {
    $agent = agent();
    $booking = familyBooking($agent);
    actingAs($agent)->get(route('bookings.itinerary', $booking))->assertOk()->assertHeader('content-type', 'application/pdf');
    actingAs(agent())->get(route('bookings.itinerary', $booking))->assertNotFound();

    expect(app(BookingDocuments::class)->itineraryHtml($booking))->toContain('Hotel Caribe');

    app(ChangeItemStatusAction::class)->execute(hotelOf($booking), BookingItemStatus::Cancelled, 'x', CarbonImmutable::now());
    expect(app(BookingDocuments::class)->itineraryHtml($booking->fresh() ?? $booking))->toContain(__('bookings.pdf.empty'))->not->toContain('Hotel Caribe');
});

it('brands documents with the agency letterhead and embedded logo', function (): void {
    Storage::fake(config('travel.organization.logo_disk'));
    $path = UploadedFile::fake()->image('logo.png')->store(config('travel.organization.logo_directory'), config('travel.organization.logo_disk'));
    AgencyProfile::factory()->create(['trade_name' => 'Viajes Demo', 'logo_path' => $path, 'brand_primary_color' => '#0f766e', 'rnt_number' => '12345']);

    $letterhead = app(AgencyLetterhead::class)->letterhead();
    $html = app(BookingDocuments::class)->itineraryHtml(familyBooking(agent()));

    expect($letterhead->logoDataUri)->toStartWith('data:image/png;base64,')
        ->and($html)->toContain('data:image/png;base64,')->toContain('#0f766e')->toContain(__('documents.rnt', ['rnt' => '12345']));
});

it('falls back to the application name without agency profile or logo', function (): void {
    $letterhead = app(AgencyLetterhead::class)->letterhead();

    expect($letterhead->tradeName)->toBe(config('app.name'))
        ->and($letterhead->logoDataUri)->toBeNull();

    AgencyProfile::factory()->create(['logo_path' => 'logos/no-existe.png', 'brand_primary_color' => 'rojo']);
    $profiled = app(AgencyLetterhead::class)->letterhead();
    expect($profiled->logoDataUri)->toBeNull()->and($profiled->primaryColor)->toBeNull();
});

it('shows download links on the quote, the customer page and the booking', function (): void {
    $agent = agent();
    $booking = familyBooking($agent);
    $quote = Quote::query()->where('ulid', $booking->quote_ulid)->sole();
    app(ConfirmItemAction::class)->execute(hotelOf($booking), 'HCR-77', null, CarbonImmutable::now());

    actingAs($agent)->get(route('quotes.show', $quote))->assertSee(route('quotes.pdf', [$quote, 1]));
    actingAs($agent)->get(route('bookings.show', $booking))->assertSee(route('bookings.itinerary', $booking))->assertSee(route('bookings.voucher', [$booking, hotelOf($booking)->ulid]));
    actingAs(userWithRole(Role::AgencyOwner));
    get((string) app(QuoteLinks::class)->customerUrl($quote))->assertSee(__('documents.download_pdf'));
});
