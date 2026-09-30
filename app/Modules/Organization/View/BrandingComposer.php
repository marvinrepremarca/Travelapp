<?php

declare(strict_types=1);

namespace App\Modules\Organization\View;

use App\Modules\Organization\Models\AgencyProfile;
use App\Modules\Shared\Accessibility\ContrastRatio;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\View\View;

/**
 * Entrega a los layouts la marca de la agencia: nombre comercial y variables CSS --brand-*.
 * Solo emite colores válidos (#rrggbb); todo lo demás usa los tokens por defecto.
 */
final readonly class BrandingComposer
{
    public const CACHE_KEY = 'organization:branding';

    public function __construct(private Cache $cache) {}

    public function compose(View $view): void
    {
        /** @var array{name: string|null, css: string} $branding */
        $branding = $this->cache->rememberForever(self::CACHE_KEY, static function (): array {
            $profile = AgencyProfile::current();
            $variables = [
                '--brand-primary' => $profile?->brand_primary_color,
                '--brand-accent' => $profile?->brand_accent_color,
            ];

            $css = collect($variables)
                ->filter(static fn(?string $color): bool => $color !== null && ContrastRatio::isHex($color))
                ->map(static fn(string $color, string $variable): string => "{$variable}:{$color}")
                ->implode(';');

            return ['name' => $profile?->trade_name, 'css' => $css];
        });

        $view->with('agencyName', $branding['name'] ?? config('app.name'))
            ->with('brandCss', $branding['css']);
    }
}
