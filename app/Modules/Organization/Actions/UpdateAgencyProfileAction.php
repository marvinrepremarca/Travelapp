<?php

declare(strict_types=1);

namespace App\Modules\Organization\Actions;

use App\Modules\Organization\Data\AgencyProfileData;
use App\Modules\Organization\Models\AgencyProfile;
use App\Modules\Organization\Services\NitCheckDigit;
use App\Modules\Organization\View\BrandingComposer;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Filesystem\Factory as Filesystem;

final readonly class UpdateAgencyProfileAction
{
    public function __construct(
        private Filesystem $storage,
        private Config $config,
        private Cache $cache,
    ) {}

    /** Crea o actualiza el perfil. El dígito de verificación siempre se calcula en el servidor. */
    public function execute(AgencyProfileData $data): AgencyProfile
    {
        $profile = AgencyProfile::current() ?? new AgencyProfile();
        $disk = $this->storage->disk($this->config->string('travel.organization.logo_disk'));
        $previousLogo = $profile->logo_path;

        $profile->fill([
            'legal_name' => $data->legalName,
            'trade_name' => $data->tradeName,
            'nit' => $data->nit,
            'nit_check_digit' => NitCheckDigit::for($data->nit),
            'rnt_number' => $data->rntNumber,
            'rnt_expires_on' => $data->rntExpiresOn->toDateString(),
            'address' => $data->address,
            'city' => $data->city,
            'phone' => $data->phone,
            'email' => $data->email,
            'website' => $data->website,
            'brand_primary_color' => $data->brandPrimaryColor,
            'brand_accent_color' => $data->brandAccentColor,
        ]);

        if ($data->logo instanceof \Illuminate\Http\UploadedFile) {
            $profile->logo_path = $disk->putFile($this->config->string('travel.organization.logo_directory'), $data->logo) ?: null;
        }

        $profile->save();

        if ($data->logo instanceof \Illuminate\Http\UploadedFile && $previousLogo !== null && $previousLogo !== $profile->logo_path) {
            $disk->delete($previousLogo);
        }

        $this->cache->forget(BrandingComposer::CACHE_KEY);

        return $profile;
    }
}
