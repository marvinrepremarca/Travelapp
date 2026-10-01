<?php

declare(strict_types=1);

namespace App\Modules\Suppliers\Actions;

use App\Modules\Suppliers\Data\SupplierData;
use App\Modules\Suppliers\Exceptions\SupplierRuleViolation;
use App\Modules\Suppliers\Models\Supplier;

/** Crea o actualiza un proveedor. Un prestador turístico colombiano exige RNT y su vencimiento. */
final class SaveSupplierAction
{
    public function execute(SupplierData $data, ?Supplier $supplier = null): Supplier
    {
        $country = mb_strtoupper($data->country);

        if ($data->isTourismProvider && $country === Supplier::RNT_COUNTRY && ($data->rntNumber === null || $data->rntNumber === '' || ! $data->rntExpiresOn instanceof \Carbon\CarbonImmutable)) {
            throw SupplierRuleViolation::rntRequired();
        }

        $isNew = ! $supplier instanceof Supplier;
        $supplier ??= new Supplier();
        $supplier->fill([
            'legal_name' => $data->legalName,
            'trade_name' => $data->tradeName,
            'tax_id' => mb_strtoupper((string) preg_replace('/[\s.\-]/', '', $data->taxId)),
            'country' => $country,
            'is_tourism_provider' => $data->isTourismProvider,
            'rnt_number' => $data->rntNumber,
            'rnt_expires_on' => $data->rntExpiresOn?->toDateString(),
            'email' => $data->email === null ? null : mb_strtolower($data->email),
            'phone' => $data->phone,
            'website' => $data->website,
            'payment_terms' => $data->paymentTerms,
            'payment_days' => $data->paymentDays,
            'payment_currency' => mb_strtoupper($data->paymentCurrency),
            'notes' => $data->notes,
        ]);

        if ($isNew) {
            $supplier->is_active = true;
        }

        $supplier->save();

        return $supplier;
    }
}
