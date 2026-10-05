<?php

declare(strict_types=1);

namespace App\Modules\Search\Contracts;

use App\Modules\Search\Data\HotelOffer;
use App\Modules\Search\Data\HotelSearchCriteria;
use App\Modules\Search\Exceptions\ProviderUnavailable;

/** Puerto de hoteles (ADR-0006): LiteAPI, Fake y, más adelante, Hotelbeds. */
interface HotelProvider extends BookableProvider
{
    public const TAG = 'search.hotel_providers';

    /**
     * @return list<HotelOffer>
     *
     * @throws ProviderUnavailable
     */
    public function search(HotelSearchCriteria $criteria): array;
}
