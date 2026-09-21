<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Domain\ValueObjects;

use App\Domain\Pelaporan\Domain\Enums\KelompokKpi;
use App\Domain\Pelaporan\Domain\Enums\SatuanKpi;

/**
 * Definisi satu KPI: nama, satuan, sumber transaksi, dan rumusnya.
 *
 * `formula` bukan komentar hiasan — ia dikirim ke klien dan ditampilkan pada
 * setiap kartu KPI, sehingga pembaca dasbor selalu dapat melihat angka itu
 * dihitung dari apa (Gate 21). Mengubah cara hitung tanpa memperbarui teks ini
 * akan membuat dasbor berbohong, jadi keduanya diuji bersama.
 */
final readonly class DefinisiKpi
{
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
    ) {}

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
            'NaikItuBaik' => $this->naikItuBaik,
        ];
    }
}
