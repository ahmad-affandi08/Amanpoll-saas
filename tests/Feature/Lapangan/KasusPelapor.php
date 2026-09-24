<?php

declare(strict_types=1);

namespace Tests\Feature\Lapangan;

use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Pemeliharaan\Application\Actions\BuatKeluhan;
use App\Domain\Pemeliharaan\Application\Actions\UbahStatusKeluhan;
use App\Domain\Pemeliharaan\Domain\Enums\StatusKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KategoriKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\NomorDokumen;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

/**
 * Data uji layar Pelapor (FASE 39.07–39.08): lokasi bertingkat, aset, kategori,
 * dan keluhan yang dibuat lewat Action Pemeliharaan yang sesungguhnya, sehingga
 * nomor, riwayat status, dan transisinya sama dengan produksi.
 */
abstract class KasusPelapor extends KasusLapangan
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->dalamOrganisasi(fn () => NomorDokumen::create([
            'JenisDokumen' => 'Keluhan',
            'Awalan' => 'KLH',
            'FormatNomor' => '{Awalan}/{Tahun}/{Nomor:4}',
            'ResetPeriode' => 'Tahunan',
        ]));
    }

    protected function lokasi(string $nama, ?Lokasi $induk = null): Lokasi
    {
        return $this->dalamOrganisasi(fn () => Lokasi::create(['Nama' => $nama, 'IndukId' => $induk?->Id, 'Status' => 'Aktif']));
    }

    /** @param array<string, mixed> $atribut */
    protected function kategori(string $nama, array $atribut = []): KategoriKeluhan
    {
        return $this->dalamOrganisasi(fn () => KategoriKeluhan::create([
            'Nama' => $nama, 'PrioritasBawaan' => 'Normal', 'AsetWajib' => false, 'Aktif' => true, ...$atribut,
        ]));
    }

    protected function aset(string $nama, Lokasi $lokasi, string $kondisi = 'Baik'): Aset
    {
        return $this->dalamOrganisasi(function () use ($nama, $lokasi, $kondisi): Aset {
            $kategori = KategoriAset::query()->firstOrCreate(['Nama' => 'Peralatan Gedung']);

            return Aset::create([
                'Nama' => $nama, 'KategoriAsetId' => $kategori->Id, 'LokasiId' => $lokasi->Id,
                'Status' => 'Aktif', 'Kondisi' => $kondisi, 'KodeQr' => 'QR-'.uniqid(), 'Versi' => 1,
            ]);
        });
    }

    /** Pelapor bawaan (peran PELAPOR) yang lingkupnya satu ruangan. */
    protected function pelaporDi(Lokasi $lokasi): Pengguna
    {
        return $this->penggunaDenganPeran(['PELAPOR'], null, $lokasi->Id);
    }

    /**
     * Keluhan lewat `BuatKeluhan`, lalu dijalankan melalui transisi status yang diminta.
     *
     * @param  array<string, mixed>  $data
     * @param  list<StatusKeluhan>  $transisi
     */
    protected function keluhan(Pengguna $pelapor, KategoriKeluhan $kategori, Lokasi $lokasi, array $data = [], array $transisi = []): Keluhan
    {
        return $this->dalamOrganisasi(function () use ($pelapor, $kategori, $lokasi, $data, $transisi): Keluhan {
            $keluhan = app(BuatKeluhan::class)->jalankan([
                'KategoriKeluhanId' => $kategori->Id, 'LokasiId' => $lokasi->Id,
                'Judul' => 'Keluhan uji', 'Deskripsi' => 'Deskripsi uji.', ...$data,
            ], $pelapor->Id);

            foreach ($transisi as $status) {
                $keluhan = app(UbahStatusKeluhan::class)->jalankan($keluhan->fresh(), $status, null, $keluhan->fresh()->Versi, $pelapor->Id);
            }

            return $keluhan->fresh();
        });
    }

    /** Perintah kerja dari keluhan, ditugaskan ke seorang teknisi. */
    protected function tugaskan(Keluhan $keluhan, Pengguna $teknisi, string $status = 'Dikerjakan'): PerintahKerja
    {
        return $this->dalamOrganisasi(function () use ($keluhan, $teknisi, $status): PerintahKerja {
            $perintahKerja = PerintahKerja::create([
                'Nomor' => 'WO-'.uniqid(), 'KeluhanId' => $keluhan->Id, 'Jenis' => 'Korektif', 'Judul' => $keluhan->Judul,
                'Prioritas' => 'Normal', 'Status' => $status, 'LokasiId' => $keluhan->LokasiId,
                'RingkasanPenyelesaian' => 'Komponen diganti, sudah normal.',
            ]);
            PenugasanPerintahKerja::create([
                'PerintahKerjaId' => $perintahKerja->Id, 'PenggunaId' => $teknisi->Id,
                'DitugaskanPada' => now(), 'Status' => 'Diterima',
            ]);

            return $perintahKerja;
        });
    }
}
