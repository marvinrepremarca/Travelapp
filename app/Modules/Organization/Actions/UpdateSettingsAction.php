<?php

declare(strict_types=1);

namespace App\Modules\Organization\Actions;

use App\Modules\Organization\Enums\SettingKey;
use App\Modules\Organization\Models\Setting;
use App\Modules\Organization\Services\SettingsStore;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final readonly class UpdateSettingsAction
{
    public function __construct(private SettingsStore $store) {}

    /**
     * Guarda solo los valores que cambian; cada cambio queda auditado.
     *
     * @param  array<string, mixed>  $values  Indexado por SettingKey::field()
     */
    public function execute(array $values): void
    {
        $rules = [];

        foreach (SettingKey::cases() as $key) {
            $rules[$key->field()] = $key->rules();
        }

        $validated = Validator::make($values, $rules, attributes: $this->attributes())->validate();

        DB::transaction(function () use ($validated): void {
            foreach (SettingKey::cases() as $key) {
                $value = $key->type()->cast($validated[$key->field()]);

                if ($value === $this->store->get($key)) {
                    continue;
                }

                Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
            }
        });

        $this->store->flush();
    }

    /** @return array<string, string> */
    private function attributes(): array
    {
        $attributes = [];

        foreach (SettingKey::cases() as $key) {
            $attributes[$key->field()] = mb_strtolower($key->label());
        }

        return $attributes;
    }
}
