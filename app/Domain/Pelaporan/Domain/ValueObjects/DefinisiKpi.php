<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Domain\ValueObjects;

use App\Domain\Pelaporan\Domain\Enums\KelompokKpi;
use App\Domain\Pelaporan\Domain\Enums\SatuanKpi;

/**
 * Definisi satu KPI: nama, satuan, sumber transaksi, dan rumusnya.
 *
 * Rincian tidak selalu bersatuan sama dengan nilai utamanya: KPI persen
 * seperti kepatuhan SLA merinci jumlah perintah kerja, bukan persen.
 */
final readonly class DefinisiKpi
{
    public SatuanKpi $satuanRincian;

    public function __construct(
        public string $kunci,
        public string $nama,
        public KelompokKpi $kelompok,
        public SatuanKpi $satuan,
        public string $formula,
        public string $sumber,
        /** Kode izin minimal; null berarti cukup pengguna aktif. */
        public ?string $izin = null,
        /** Apakah nilai yang lebih besar berarti lebih baik; null bila netral. */
        public ?bool $naikItuBaik = null,
        ?SatuanKpi $satuanRincian = null,
    ) {
        $this->satuanRincian = $satuanRincian ?? $satuan;
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
            'SatuanRincian' => $this->satuanRincian->value,
            'DesimalRincian' => $this->satuanRincian->desimal(),
            'Formula' => $this->formula,
            'Sumber' => $this->sumber,
            'NaikItuBaik' => $this->naikItuBaik,
        ];
    }
}
