<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/** Jenis field form builder (MARKETING.md 10). */
enum JenisFieldFormulir: string
{
    case Teks = 'Teks';
    case Email = 'Email';
    case Telepon = 'Telepon';
    case Angka = 'Angka';
    case Pilihan = 'Pilihan';
    case PilihanGanda = 'PilihanGanda';
    case KotakCentang = 'KotakCentang';
    case Radio = 'Radio';
    case AreaTeks = 'AreaTeks';
    case UtmTersembunyi = 'UtmTersembunyi';
    case Persetujuan = 'Persetujuan';

    /** Field yang daftar pilihannya wajib diisi saat formulir disusun. */
    public function butuhPilihan(): bool
    {
        return in_array($this, [self::Pilihan, self::PilihanGanda, self::Radio], true);
    }

    /**
     * Field yang tidak diisi pengunjung. Nilainya datang dari kunjungan, bukan
     * dari ketikan, jadi ia tidak pernah divalidasi sebagai masukan wajib.
     */
    public function terisiOtomatis(): bool
    {
        return $this === self::UtmTersembunyi;
    }

    /** Aturan validasi dasar untuk nilai yang dikirim pengunjung. */
    public function aturanValidasi(): string
    {
        return match ($this) {
            self::Email => 'email:rfc|max:190',
            self::Telepon => 'string|max:60',
            self::Angka => 'numeric',
            self::AreaTeks => 'string|max:5000',
            self::PilihanGanda => 'array',
            self::KotakCentang, self::Persetujuan => 'boolean',
            default => 'string|max:500',
        };
    }
}
