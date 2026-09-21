<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Application\Services;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Pelaporan\Domain\Enums\BentukKomponen;
use App\Domain\Pelaporan\Infrastructure\Persistence\Models\DasborTersimpan;
use App\Domain\Pelaporan\Infrastructure\Persistence\Models\KomponenDasbor;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Illuminate\Database\Eloquent\Collection;

/**
 * Susunan dasbor: preset bawaan per peran (21.02) dan dasbor tersimpan milik
 * pengguna (21.04).
 *
 * Preset dipilih dari izin yang benar-benar dimiliki pengguna, bukan dari nama
 * peran, sehingga organisasi yang menamai perannya berbeda tetap mendapat
 * dasbor yang sesuai kewenangannya. KPI yang tidak diizinkan disaring belakangan
 * oleh LayananMetrik, jadi preset boleh menyebut KPI apa pun tanpa risiko bocor.
 */
final class LayananDasbor
{
    /** Lebar komponen dalam grid 4 kolom. */
    public const LEBAR_MIN = 1;

    public const LEBAR_MAKS = 4;

    public function __construct(private readonly PemeriksaIzin $izin) {}

    /**
     * @return array{Kunci: string, Nama: string, Komponen: array<int, array<string, mixed>>}
     */
    public function preset(Pengguna $pengguna): array
    {
        if ($this->izin->boleh($pengguna->Id, 'Laporan.Lihat')) {
            return $this->presetManajemen();
        }

        if ($this->izin->boleh($pengguna->Id, 'PerintahKerja.Kelola')
            || $this->izin->boleh($pengguna->Id, 'Pemeliharaan.Kelola')) {
            return $this->presetSupervisor();
        }

        return $this->presetTeknisi();
    }

    /** @return array{Kunci: string, Nama: string, Komponen: array<int, array<string, mixed>>} */
    private function presetTeknisi(): array
    {
        return [
            'Kunci' => 'teknisi',
            'Nama' => 'Dasbor Teknisi',
            'Komponen' => [
                $this->komponen('perintah_kerja.aktif', BentukKomponen::Angka, 1),
                $this->komponen('perintah_kerja.terlambat', BentukKomponen::Angka, 1),
                $this->komponen('preventif.jatuh_tempo', BentukKomponen::Angka, 1),
                $this->komponen('sla.berisiko', BentukKomponen::Angka, 1),
                $this->komponen('perintah_kerja.aktif', BentukKomponen::Batang, 2, 'Pekerjaan per status'),
                $this->komponen('perintah_kerja.selesai', BentukKomponen::Garis, 2, 'Penyelesaian harian'),
            ],
        ];
    }

    /** @return array{Kunci: string, Nama: string, Komponen: array<int, array<string, mixed>>} */
    private function presetSupervisor(): array
    {
        return [
            'Kunci' => 'supervisor',
            'Nama' => 'Dasbor Supervisor',
            'Komponen' => [
                $this->komponen('keluhan.terbuka', BentukKomponen::Angka, 1),
                $this->komponen('perintah_kerja.aktif', BentukKomponen::Angka, 1),
                $this->komponen('perintah_kerja.terlambat', BentukKomponen::Angka, 1),
                $this->komponen('sla.berisiko', BentukKomponen::Angka, 1),
                $this->komponen('sla.kepatuhan_penyelesaian', BentukKomponen::Angka, 2),
                $this->komponen('preventif.kepatuhan', BentukKomponen::Angka, 2),
                $this->komponen('perintah_kerja.aktif', BentukKomponen::Batang, 2, 'Pekerjaan per status'),
                $this->komponen('keluhan.masuk', BentukKomponen::Garis, 2, 'Keluhan masuk per hari'),
                $this->komponen('stok.di_bawah_minimum', BentukKomponen::Tabel, 2, 'Suku cadang di bawah minimum'),
                $this->komponen('kalibrasi.jatuh_tempo', BentukKomponen::Batang, 2, 'Kalibrasi jatuh tempo'),
            ],
        ];
    }

    /** @return array{Kunci: string, Nama: string, Komponen: array<int, array<string, mixed>>} */
    private function presetManajemen(): array
    {
        return [
            'Kunci' => 'manajemen',
            'Nama' => 'Dasbor Manajemen',
            'Komponen' => [
                $this->komponen('aset.nilai_perolehan', BentukKomponen::Angka, 1),
                $this->komponen('biaya.pemeliharaan', BentukKomponen::Angka, 1),
                $this->komponen('downtime.ketersediaan', BentukKomponen::Angka, 1),
                $this->komponen('sla.kepatuhan_penyelesaian', BentukKomponen::Angka, 1),
                $this->komponen('keandalan.mttr', BentukKomponen::Angka, 1),
                $this->komponen('keandalan.mtbf', BentukKomponen::Angka, 1),
                $this->komponen('anggaran.serapan', BentukKomponen::Angka, 1),
                $this->komponen('kepatuhan.tingkat', BentukKomponen::Angka, 1),
                $this->komponen('biaya.pemeliharaan', BentukKomponen::Garis, 2, 'Biaya pemeliharaan per bulan'),
                $this->komponen('aset.kondisi', BentukKomponen::Donat, 2, 'Distribusi kondisi aset'),
                $this->komponen('downtime.total_jam', BentukKomponen::Garis, 2, 'Downtime per bulan'),
                $this->komponen('anggaran.serapan', BentukKomponen::Batang, 2, 'Serapan per anggaran'),
                $this->komponen('kontrak.akan_berakhir', BentukKomponen::Batang, 2, 'Kontrak akan berakhir'),
                $this->komponen('pengadaan.nilai_pesanan', BentukKomponen::Batang, 2, 'Nilai pesanan per status'),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function komponen(string $kunciKpi, BentukKomponen $bentuk, int $lebar, ?string $judul = null): array
    {
        return [
            'Id' => $kunciKpi.':'.$bentuk->value,
            'JenisKomponen' => 'Kpi',
            'Judul' => $judul,
            'KunciKpi' => $kunciKpi,
            'Bentuk' => $bentuk->value,
            'Lebar' => $lebar,
        ];
    }

    /**
     * Mengubah dasbor tersimpan menjadi bentuk yang sama dengan preset,
     * sehingga klien hanya mengenal satu bentuk susunan.
     *
     * @return array{Kunci: string, Nama: string, Komponen: array<int, array<string, mixed>>}
     */
    public function dariTersimpan(DasborTersimpan $dasbor): array
    {
        return [
            'Kunci' => $dasbor->Id,
            'Nama' => $dasbor->Nama,
            'Komponen' => $dasbor->komponen
                ->map(fn (KomponenDasbor $komponen): array => [
                    'Id' => $komponen->Id,
                    'JenisKomponen' => $komponen->JenisKomponen,
                    'Judul' => $komponen->Judul,
                    'KunciKpi' => (string) ($komponen->Konfigurasi['KunciKpi'] ?? ''),
                    'Bentuk' => (string) ($komponen->Konfigurasi['Bentuk'] ?? BentukKomponen::Angka->value),
                    'Lebar' => $komponen->Lebar,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * Dasbor tersimpan yang boleh dilihat pengguna: miliknya sendiri, atau
     * dasbor organisasi yang tidak dimiliki siapa pun.
     *
     * @return Collection<int, DasborTersimpan>
     */
    public function dasborUntuk(Pengguna $pengguna): Collection
    {
        return DasborTersimpan::query()
            ->with('komponen')
            ->where(fn ($query) => $query->where('PemilikId', $pengguna->Id)->orWhereNull('PemilikId'))
            ->orderByDesc('Bawaan')
            ->orderBy('Nama')
            ->get();
    }
}
