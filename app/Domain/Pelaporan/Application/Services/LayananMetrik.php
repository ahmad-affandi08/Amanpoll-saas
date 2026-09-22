<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Application\Services;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Pelaporan\Domain\KatalogKpi;
use App\Domain\Pelaporan\Domain\ValueObjects\DefinisiKpi;
use App\Domain\Pelaporan\Domain\ValueObjects\FilterMetrik;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

/** Titik masuk tunggal untuk menghitung KPI (21.01/21.02). */
final class LayananMetrik
{
    public function __construct(
        private readonly RegistriKpi $registri,
        private readonly PemeriksaIzin $izin,
    ) {}

    /**
     * Menghitung sekumpulan KPI untuk satu pengguna. Kunci yang tidak dikenal
     * atau tidak diizinkan dilewati diam-diam supaya konfigurasi dasbor lama
     * tidak membuat halaman gagal dimuat seluruhnya.
     *
     * @param  array<int, string>  $kunci
     * @return array<string, array<string, mixed>>
     */
    public function hitungBanyak(array $kunci, FilterMetrik $filter, Pengguna $pengguna): array
    {
        $hasil = [];

        foreach (array_unique($kunci) as $satuKunci) {
            if (! KatalogKpi::ada($satuKunci) || ! $this->registri->ada($satuKunci)) {
                continue;
            }

            $definisi = KatalogKpi::ambil($satuKunci);
            if (! $this->boleh($definisi, $pengguna)) {
                continue;
            }

            $hasil[$satuKunci] = [
                ...$definisi->keArray(),
                ...$this->registri->untuk($satuKunci)->hitung($satuKunci, $filter)->keArray(),
            ];
        }

        return $hasil;
    }

    /**
     * Definisi KPI yang boleh dilihat pengguna, untuk pemilih komponen dasbor
     * kustom dan pemilih laporan.
     *
     * @return list<array<string, mixed>>
     */
    public function katalogUntuk(Pengguna $pengguna): array
    {
        return array_values(array_map(
            fn (DefinisiKpi $definisi): array => $definisi->keArray(),
            array_filter(
                KatalogKpi::semua(),
                fn (DefinisiKpi $definisi): bool => $this->boleh($definisi, $pengguna)
                    && $this->registri->ada($definisi->kunci),
            ),
        ));
    }

    /**
     * @param  array<int, string>  $kunci
     * @return list<string>
     */
    public function saringYangDiizinkan(array $kunci, Pengguna $pengguna): array
    {
        return array_values(array_filter(
            $kunci,
            fn (string $satu): bool => KatalogKpi::ada($satu)
                && $this->boleh(KatalogKpi::ambil($satu), $pengguna),
        ));
    }

    public function boleh(DefinisiKpi $definisi, Pengguna $pengguna): bool
    {
        if ($pengguna->Status !== 'Aktif') {
            return false;
        }

        return $definisi->izin === null || $this->izin->boleh($pengguna->Id, $definisi->izin);
    }
}
