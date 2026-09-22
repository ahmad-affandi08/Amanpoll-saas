<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Infrastructure\Services;

use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\IntegrasiEksternal;

/** Menyusun header autentikasi dari konfigurasi terenkripsi sebuah integrasi. */
final class PenyusunHeaderIntegrasi
{
    /**
     * @return array<string, string>
     */
    public function untuk(IntegrasiEksternal $integrasi): array
    {
        $konfigurasi = $integrasi->KonfigurasiTerenkripsi ?? [];

        return match ($integrasi->MetodeAutentikasi) {
            'Bearer' => ['Authorization' => 'Bearer '.(string) ($konfigurasi['Token'] ?? '')],
            'ApiKey' => [(string) ($konfigurasi['NamaHeader'] ?? 'X-Api-Key') => (string) ($konfigurasi['Kunci'] ?? '')],
            default => [],
        };
    }
}
