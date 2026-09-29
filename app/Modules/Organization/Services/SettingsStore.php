<?php

declare(strict_types=1);

namespace App\Modules\Organization\Services;

use App\Modules\Organization\Enums\SettingKey;
use App\Modules\Organization\Models\Setting;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;

/** Lee parámetros: valor guardado en `settings` o, si no hay, el default de config/travel.php. Cacheado. */
final readonly class SettingsStore
{
    public const CACHE_KEY = 'organization:settings';

    public function __construct(
        private Cache $cache,
        private Config $config,
    ) {}

    public function get(SettingKey $key): string|int|bool
    {
        $overrides = $this->overrides();

        $value = array_key_exists($key->value, $overrides) ? $overrides[$key->value] : $this->config->get($key->configPath());

        return $key->type()->cast($value);
    }

    public function isOverridden(SettingKey $key): bool
    {
        return array_key_exists($key->value, $this->overrides());
    }

    public function flush(): void
    {
        $this->cache->forget(self::CACHE_KEY);
    }

    /** @return array<string, mixed> */
    private function overrides(): array
    {
        /** @var array<string, mixed> */
        return $this->cache->rememberForever(self::CACHE_KEY, static fn(): array => Setting::query()
            ->get()
            ->mapWithKeys(static fn(Setting $setting): array => [$setting->key->value => $setting->value])
            ->all());
    }
}
