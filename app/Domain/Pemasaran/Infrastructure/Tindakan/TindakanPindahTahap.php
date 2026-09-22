<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Tindakan;

use App\Domain\Pemasaran\Application\Actions\PindahkanTahapProspek;
use App\Domain\Pemasaran\Domain\Contracts\TindakanOtomasi;
use App\Domain\Pemasaran\Domain\ValueObjects\KonteksOtomasi;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\TahapPipeline;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/** Memindahkan prospek ke satu tahap pipeline. */
final class TindakanPindahTahap implements TindakanOtomasi
{
    public function __construct(private readonly PindahkanTahapProspek $pindahkan) {}

    public function kode(): string
    {
        return 'PindahTahap';
    }

    public function label(): string
    {
        return 'Pindah tahap pipeline';
    }

    /** @return array<string, mixed> */
    public function aturan(): array
    {
        return ['TahapKode' => ['required', 'string', 'exists:TahapPipeline,Kode']];
    }

    /** @param array<string, mixed> $konfigurasi */
    public function jalankan(KonteksOtomasi $konteks, array $konfigurasi): string
    {
        $kode = (string) ($konfigurasi['TahapKode'] ?? '');
        $tahap = TahapPipeline::query()->where('Kode', $kode)->first();

        if ($tahap === null) {
            throw new AturanBisnisDilanggar("Tahap pipeline {$kode} tidak ada.");
        }

        $prospek = $konteks->wajibProspek();

        if ($prospek->TahapPipelineId === $tahap->Id) {
            return "Prospek sudah berada di tahap {$kode}.";
        }

        $this->pindahkan->jalankan($prospek, $tahap, 'Dipindahkan otomasi pemasaran.');

        return "Prospek dipindahkan ke tahap {$kode}.";
    }
}
