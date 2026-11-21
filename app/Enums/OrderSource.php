<?php

namespace App\Enums;

/** Nilai enum = isi kolom orders.source yang sudah dipakai view. */
enum OrderSource: string
{
    case Meja = 'Meja';
    case GoFood = 'GoFood';
    case GrabFood = 'GrabFood';
    case ShopeeFood = 'ShopeeFood';

    /** Kode di URL webhook: gofood | grabfood | shopeefood */
    public function slug(): string
    {
        return strtolower($this->value);
    }

    public static function fromSlug(string $slug): ?self
    {
        foreach (self::marketplaces() as $source) {
            if ($source->slug() === strtolower($slug)) {
                return $source;
            }
        }

        return null;
    }

    /** @return array<self> */
    public static function marketplaces(): array
    {
        return [self::GoFood, self::GrabFood, self::ShopeeFood];
    }

    /** @return array<string> */
    public static function marketplaceValues(): array
    {
        return array_map(fn (self $s) => $s->value, self::marketplaces());
    }
}
