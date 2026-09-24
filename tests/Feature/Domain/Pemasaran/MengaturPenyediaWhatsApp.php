<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Platform\Domain\Enums\KategoriPenyediaLayanan;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenyediaLayananPlatform;

/** Menyimpan kredensial penyedia WhatsApp seperti konsol platform menyimpannya: terenkripsi, di satu baris per kode. */
trait MengaturPenyediaWhatsApp
{
    /** @param array<string, string> $kredensial */
    protected function aturPenyedia(
        string $kode,
        array $kredensial,
        bool $utama = true,
        bool $aktif = true,
        bool $modeUji = false,
    ): PenyediaLayananPlatform {
        return PenyediaLayananPlatform::create([
            'Kategori' => KategoriPenyediaLayanan::WhatsApp,
            'Kode' => $kode,
            'Aktif' => $aktif,
            'Utama' => $utama,
            'ModeUji' => $modeUji,
            'KredensialTerenkripsi' => $kredensial,
        ]);
    }

    protected function aturMetaCloud(): PenyediaLayananPlatform
    {
        return $this->aturPenyedia('MetaCloud', [
            'PhoneNumberId' => '1098765',
            'WabaId' => '2024001',
            'AccessToken' => 'token-meta-rahasia',
            'AppSecret' => 'app-secret-rahasia',
            'VerifyToken' => 'verifikasi-rahasia',
        ]);
    }
}
