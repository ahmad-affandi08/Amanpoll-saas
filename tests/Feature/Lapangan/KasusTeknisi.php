<?php

declare(strict_types=1);

namespace Tests\Feature\Lapangan;

use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerjaAset;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;

/**
 * Data dasar test layar Teknisi Mode Lapangan (FASE 39.04–39.06): aset, tiket kerja
 * yang ditugaskan, dan organisasi kedua untuk membuktikan batas tenant.
 */
abstract class KasusTeknisi extends KasusLapangan
{
    protected function buatAset(string $nama = 'Lift Penumpang 3', array $lain = []): Aset
    {
        return $this->dalamOrganisasi(function () use ($nama, $lain): Aset {
            $kategori = KategoriAset::query()->firstOrCreate(['Kode' => 'KAT-UJI'], ['Nama' => 'Lift & Eskalator']);

            return Aset::create([
                'KategoriAsetId' => $kategori->Id,
                'KodeAset' => 'AST-'.strtoupper(substr(uniqid(), -6)),
                'KodeQr' => 'QR-'.uniqid(),
                'Nama' => $nama,
                'Status' => 'Aktif',
                'Kondisi' => 'Rusak',
                ...$lain,
            ]);
        });
    }

    /** Tiket kerja; bila `$teknisi` diisi, ia ditugaskan (penugasan Ditugaskan, atau Diterima bila tiket sudah diterima). */
    protected function buatTiket(
        ?Pengguna $teknisi,
        StatusPerintahKerja $status = StatusPerintahKerja::Ditugaskan,
        ?Aset $aset = null,
        array $lain = [],
    ): PerintahKerja {
        return $this->dalamOrganisasi(function () use ($teknisi, $status, $aset, $lain): PerintahKerja {
            $tiket = PerintahKerja::create([
                'Nomor' => 'PK-UJI-'.uniqid(),
                'Jenis' => 'Korektif',
                'Judul' => 'Lift berhenti di antara lantai',
                'Prioritas' => 'Kritis',
                'Status' => $status->value,
                'BatasPenyelesaianPada' => now()->addHours(2),
                ...$lain,
            ]);

            if ($aset !== null) {
                PerintahKerjaAset::create(['PerintahKerjaId' => $tiket->Id, 'AsetId' => $aset->Id, 'Utama' => true]);
            }

            if ($teknisi !== null) {
                PenugasanPerintahKerja::create([
                    'PerintahKerjaId' => $tiket->Id,
                    'PenggunaId' => $teknisi->Id,
                    'Status' => $status === StatusPerintahKerja::Ditugaskan ? 'Ditugaskan' : 'Diterima',
                    'DitugaskanPada' => now()->subMinutes(10),
                ]);
            }

            return $tiket;
        });
    }

    /** Organisasi lain beserta satu tiket dan asetnya, untuk test batas tenant. */
    protected function tiketOrganisasiLain(): PerintahKerja
    {
        $asli = $this->organisasi;
        $this->organisasi = Organisasi::create(['Kode' => 'ORG-LAIN-'.uniqid(), 'Nama' => 'Organisasi Lain', 'Status' => 'Aktif']);

        try {
            $tiket = $this->buatTiket(null, aset: $this->buatAset('Genset tenant lain'));
        } finally {
            $this->organisasi = $asli;
        }

        return $tiket;
    }

    protected function statusTiket(PerintahKerja $tiket): string
    {
        return (string) $this->dalamOrganisasi(fn () => PerintahKerja::query()->whereKey($tiket->Id)->value('Status'));
    }

    protected function versiTiket(PerintahKerja $tiket): int
    {
        return (int) $this->dalamOrganisasi(fn () => PerintahKerja::query()->whereKey($tiket->Id)->value('Versi'));
    }

    /** Peran bawaan menurut kode, di organisasi test. */
    protected function peran(string $kode): Peran
    {
        return $this->dalamOrganisasi(fn (): Peran => Peran::query()->where('Kode', $kode)->firstOrFail());
    }
}
