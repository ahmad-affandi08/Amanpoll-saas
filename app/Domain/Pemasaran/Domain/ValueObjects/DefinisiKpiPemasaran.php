<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\ValueObjects;

use App\Domain\Pelaporan\Domain\Enums\SatuanKpi;
use App\Domain\Pemasaran\Domain\Enums\KelompokKpiPemasaran;

/** Definisi satu KPI growth: rumus, sumber transaksinya, dan apakah sumbernya sudah ada. */
final readonly class DefinisiKpiPemasaran
{
    public function __construct(
        public string $kunci,
        public string $nama,
        public KelompokKpiPemasaran $kelompok,
        public SatuanKpi $satuan,
        public string $formula,
        public string $sumber,
        /** Alasan KPI ini belum dapat dihitung; null berarti sumbernya sudah ada. */
        public ?string $belumTersedia = null,
        public ?bool $naikItuBaik = true,
    ) {}

    public function tersedia(): bool
    {
        return $this->belumTersedia === null;
    }

    /** @return array<string, mixed> */
    public function keArray(): array
    {
        return [
            'Kunci' => $this->kunci,
            'Nama' => $this->nama,
            'Kelompok' => $this->kelompok->value,
            'LabelKelompok' => $this->kelompok->label(),
            'Satuan' => $this->satuan->value,
            'Desimal' => $this->satuan->desimal(),
            'Formula' => $this->formula,
            'Sumber' => $this->sumber,
            'Tersedia' => $this->tersedia(),
            'BelumTersedia' => $this->belumTersedia,
            'NaikItuBaik' => $this->naikItuBaik,
        ];
    }
}
