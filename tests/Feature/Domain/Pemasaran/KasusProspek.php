<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Domain\KatalogFiturPlatform;
use App\Domain\Pemasaran\Domain\KatalogTahapPipeline;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\TahapPipeline;

/** Dasar test CRM: tahap pipeline disemai, modul CRM dinyalakan. */
abstract class KasusProspek extends KasusPemasaran
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (KatalogTahapPipeline::bawaan() as $tahap) {
            TahapPipeline::query()->firstOrCreate(['Kode' => $tahap['Kode']], [
                'Nama' => $tahap['Nama'],
                'Urutan' => $tahap['Urutan'],
                'TahapAkhir' => $tahap['TahapAkhir'],
                'DianggapMenang' => $tahap['DianggapMenang'],
            ]);
        }

        $this->nyalakanFitur(KatalogFiturPlatform::CRM);
    }
}
