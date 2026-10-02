<?php

declare(strict_types=1);

namespace App\Modules\Organization\Services;

use App\Modules\Organization\Contracts\AgencyLetterhead;
use App\Modules\Organization\Data\Letterhead;
use App\Modules\Organization\Models\AgencyProfile;
use App\Modules\Shared\Accessibility\ContrastRatio;
use Illuminate\Support\Facades\Storage;

final class ProfileLetterhead implements AgencyLetterhead
{
    public function letterhead(): Letterhead
    {
        $profile = AgencyProfile::current();
        if (! $profile instanceof AgencyProfile) {
            return new Letterhead(config()->string('app.name'), null, null, null, null, null, null, null, null, null);
        }

        return new Letterhead(
            tradeName: $profile->trade_name,
            legalName: $profile->legal_name,
            nit: $profile->formattedNit(),
            rntNumber: $profile->rnt_number,
            address: $profile->address . ', ' . $profile->city,
            phone: $profile->phone,
            email: $profile->email,
            website: $profile->website,
            logoDataUri: $this->logo($profile->logo_path),
            primaryColor: $profile->brand_primary_color !== null && ContrastRatio::isHex($profile->brand_primary_color) ? $profile->brand_primary_color : null,
        );
    }

    private function logo(?string $path): ?string
    {
        $disk = Storage::disk(config()->string('travel.organization.logo_disk'));
        if ($path === null || ! $disk->exists($path)) {
            return null;
        }

        return 'data:' . $disk->mimeType($path) . ';base64,' . base64_encode((string) $disk->get($path));
    }
}
