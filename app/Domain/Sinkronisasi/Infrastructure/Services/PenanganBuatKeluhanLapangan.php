<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Infrastructure\Services;

use App\Domain\Pemeliharaan\Http\Requests\SimpanKeluhanRequest;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Sinkronisasi\Application\Services\LayananLaporanLapangan;
use App\Domain\Sinkronisasi\Domain\Contracts\PenanganOperasiSinkronisasi;
use App\Domain\Sinkronisasi\Infrastructure\Persistence\Models\AntrianSinkronisasi;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Support\Facades\Gate;

/**
 * Laporan kerusakan yang ditulis pelapor saat sinyal hilang (PRD 8.17, 8.20; DESIGN §36.7 layar 08).
 *
 * Keluhan belum punya Id di perangkat, jadi operasi ini tidak menyebut EntitasId.
 * Pengiriman ulang dari antrean dicegah `KunciOperasi`; kiriman online yang
 * jawabannya hilang lalu ikut diantrekan dicegah `KunciLaporan` di
 * `LayananLaporanLapangan`. Foto tidak ikut antrean; pelapor menambahkannya
 * dari Lacak laporan sesudah online.
 */
final class PenanganBuatKeluhanLapangan implements PenanganOperasiSinkronisasi
{
    public function __construct(private readonly LayananLaporanLapangan $layanan) {}

    public function operasi(): string
    {
        return 'Keluhan.Buat';
    }

    public function jenisEntitas(): string
    {
        return 'Keluhan';
    }

    public function membutuhkanEntitas(): bool
    {
        return false;
    }

    public function aturan(): array
    {
        $aturan = [];
        foreach (LayananLaporanLapangan::aturan((new SimpanKeluhanRequest)->rules()) as $kolom => $isi) {
            $aturan["MuatanData.{$kolom}"] = $isi;
        }

        return $aturan;
    }

    public function versiServer(?string $entitasId): ?int
    {
        return null;
    }

    public function diizinkan(AntrianSinkronisasi $antrian, Pengguna $pengguna): bool
    {
        return Gate::forUser($pengguna)->allows('create', Keluhan::class);
    }

    public function periksaKonflik(AntrianSinkronisasi $antrian): ?array
    {
        return null;
    }

    public function terapkan(AntrianSinkronisasi $antrian, Pengguna $pengguna): array
    {
        // Lingkup dan aset wajib diperiksa saat diterapkan, supaya alasannya sampai
        // ke antrean perangkat sebagai pesan yang bisa dibaca pelapor.
        $pelanggaran = $this->layanan->pelanggaran($antrian->MuatanData);
        if ($pelanggaran !== []) {
            throw new AturanBisnisDilanggar(implode(' ', $pelanggaran));
        }

        $hasil = $this->layanan->laporkan($antrian->MuatanData, $pengguna);

        return ['Id' => $hasil['keluhan']->Id, 'Nomor' => $hasil['keluhan']->Nomor];
    }
}
