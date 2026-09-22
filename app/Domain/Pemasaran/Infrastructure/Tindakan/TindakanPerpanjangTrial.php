<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Tindakan;

use App\Domain\Pemasaran\Application\Actions\PerpanjangTrial;
use App\Domain\Pemasaran\Domain\Contracts\TindakanOtomasi;
use App\Domain\Pemasaran\Domain\ValueObjects\KonteksOtomasi;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Trial;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/**
 * Memperpanjang trial lewat aksi domainnya, bukan dengan menyentuh Langganan langsung.
 *
 * `PerpanjangTrial` yang memegang batas kebijakan dan yang memanggil
 * `KelolaLangganan`; otomasi tidak boleh punya jalur pintas ke tabel langganan,
 * sebab batas perpanjangan justru ada untuk menahan pemberian otomatis.
 */
final class TindakanPerpanjangTrial implements TindakanOtomasi
{
    public function __construct(private readonly PerpanjangTrial $perpanjang) {}

    public function kode(): string
    {
        return 'PerpanjangTrial';
    }

    public function label(): string
    {
        return 'Perpanjang trial';
    }

    /** @return array<string, mixed> */
    public function aturan(): array
    {
        return ['Hari' => ['required', 'integer', 'between:1,60']];
    }

    /** @param array<string, mixed> $konfigurasi */
    public function jalankan(KonteksOtomasi $konteks, array $konfigurasi): string
    {
        $organisasiId = $konteks->organisasiId ?? $konteks->prospek?->OrganisasiId;

        if ($organisasiId === null) {
            throw new AturanBisnisDilanggar('Perpanjangan trial membutuhkan organisasi.');
        }

        $trial = Trial::query()->where('OrganisasiId', $organisasiId)->first();

        if ($trial === null) {
            return 'Organisasi ini tidak punya trial untuk diperpanjang.';
        }

        $hari = (int) ($konfigurasi['Hari'] ?? 0);
        $this->perpanjang->jalankan($trial, $hari, 'Diperpanjang otomasi pemasaran.');

        return "Trial diperpanjang {$hari} hari.";
    }
}
