<?php

namespace App\Enums;

/**
 * Nilai enum sama persis dengan string yang sudah dipakai view (baru, diproses, ...).
 * Kolom orders.status tetap string biasa; enum ini hanya dipakai untuk aturan alur.
 */
enum OrderStatus: string
{
    case Baru = 'baru';
    case Diproses = 'diproses';
    case Siap = 'siap';
    case Selesai = 'selesai';
    case Dibatalkan = 'dibatalkan';

    /** @return array<self> status tujuan yang boleh dari status ini */
    public function allowedNext(): array
    {
        return match ($this) {
            self::Baru => [self::Diproses, self::Dibatalkan],
            self::Diproses => [self::Siap, self::Dibatalkan],
            self::Siap => [self::Selesai],
            default => [],
        };
    }

    public function canMoveTo(self $to): bool
    {
        return in_array($to, $this->allowedNext(), true);
    }

    /** @return array<string> */
    public static function activeValues(): array
    {
        return [self::Baru->value, self::Diproses->value, self::Siap->value];
    }

    /** @return array<string> */
    public static function finishedValues(): array
    {
        return [self::Selesai->value, self::Dibatalkan->value];
    }

    /** Status yang dihitung sebagai pendapatan (sesuai dashboard: siap + selesai). */
    public static function revenueValues(): array
    {
        return [self::Siap->value, self::Selesai->value];
    }

    /** @return array<string> */
    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }
}
