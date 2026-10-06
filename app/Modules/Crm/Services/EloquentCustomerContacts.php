<?php

declare(strict_types=1);

namespace App\Modules\Crm\Services;

use App\Modules\Crm\Contracts\CustomerContacts;
use App\Modules\Crm\Data\CustomerWhatsApp;
use App\Modules\Crm\Enums\ConsentPurpose;
use App\Modules\Crm\Models\Customer;
use App\Modules\Crm\Models\CustomerConsent;

final class EloquentCustomerContacts implements CustomerContacts
{
    public function whatsApp(int $customerId): ?CustomerWhatsApp
    {
        $customer = Customer::query()->find($customerId, ['id', 'display_name', 'phone']);
        if (! $customer instanceof Customer || $customer->phone === null || trim($customer->phone) === '') {
            return null;
        }

        $consent = CustomerConsent::query()
            ->where('customer_id', $customer->id)
            ->where('purpose', ConsentPurpose::DataProcessing)
            ->latest('recorded_at')
            ->latest('id')
            ->first(['granted']);

        return $consent instanceof CustomerConsent && $consent->granted
            ? new CustomerWhatsApp($customer->id, $customer->display_name, $customer->phone)
            : null;
    }
}
