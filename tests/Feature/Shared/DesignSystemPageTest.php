<?php

declare(strict_types=1);

use App\Modules\Shared\Enums\ProductType;
use App\Modules\Shared\Enums\Tone;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

function enableShowcase(bool $enabled = true): void
{
    config(['travel.ui.showcase_enabled' => $enabled]);
}

it('responds not found unless enabled', function (): void {
    enableShowcase(false);

    actingAs(agent())->get(route('design-system'))->assertNotFound();
});

it('requires authentication', function (): void {
    enableShowcase();

    get(route('design-system'))->assertRedirect(route('login'));
});

it('renders every component with accessible markup', function (): void {
    enableShowcase();

    $response = actingAs(agent())->get(route('design-system'))->assertOk();

    foreach (Tone::cases() as $tone) {
        $response->assertSee(__("design_system.tones.{$tone->value}"));
    }

    $response
        ->assertSee(ProductType::DayTrip->label())
        ->assertSee('<caption class="sr-only">' . __('design_system.table_caption') . '</caption>', false)
        ->assertSee('<label for="sample"', false)
        ->assertSee('aria-describedby="sample-hint"', false)
        ->assertSee('aria-invalid="true"', false)
        ->assertSee('role="alert"', false)
        ->assertSee('role="status"', false)
        ->assertSee('href="#main"', false)
        ->assertSee(__('shared.loading'));
});

it('marks invalid inputs from the validation bag', function (): void {
    $html = (string) $this->withViewErrors(['sample' => __('design_system.sample_error')])
        ->blade('<x-ui.field label="L" for="sample"><x-ui.input name="sample" /></x-ui.field>');

    expect($html)->toContain('aria-invalid="true"')
        ->toContain('aria-describedby="sample-error"')
        ->toContain(__('design_system.sample_error'));
});
