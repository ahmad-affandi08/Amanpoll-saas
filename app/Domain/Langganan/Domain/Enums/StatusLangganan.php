<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Domain\Enums;

/** Siklus hidup langganan (22.04). */
enum StatusLangganan: string
{
    case UjiCoba = 'UjiCoba';
    case Aktif = 'Aktif';
    case Tenggang = 'Tenggang';
    case Kedaluwarsa = 'Kedaluwarsa';
    case Dibatalkan = 'Dibatalkan';

    public function label(): string
    {
        return match ($this) {
            self::UjiCoba => 'Uji Coba',
            self::Aktif => 'Aktif',
            self::Tenggang => 'Masa Tenggang',
            self::Kedaluwarsa => 'Kedaluwarsa',
            self::Dibatalkan => 'Dibatalkan',
        };
    }

    /** Status yang masih memberi akses penuh, termasuk menulis. */
    public function memberiAksesPenuh(): bool
    {
        return match ($this) {
            self::UjiCoba, self::Aktif, self::Tenggang => true,
            self::Kedaluwarsa, self::Dibatalkan => false,
        };
    }

    /** Alasan yang ditampilkan saat akses tulis ditolak. */
    public function alasanTulisDitolak(): string
    {
        return match ($this) {
            self::Kedaluwarsa => 'Langganan organisasi Anda sudah kedaluwarsa. '
                .'Data lama tetap dapat dibaca, tetapi perubahan baru ditolak sampai tagihan dilunasi.',
            self::Dibatalkan => 'Langganan organisasi Anda sudah dibatalkan. '
                .'Data lama tetap dapat dibaca; hubungi administrator untuk mengaktifkan kembali.',
            default => 'Langganan organisasi Anda sedang tidak memberi akses tulis.',
        };
    }
}
