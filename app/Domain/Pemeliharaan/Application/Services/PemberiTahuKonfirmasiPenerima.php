<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Application\Services;

use App\Core\Izin\ScopeLingkup;
use App\Domain\Notifikasi\Application\Services\LayananNotifikasi;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;

/**
 * Notifikasi seputar konfirmasi penerima (PRD 8.22): permintaan konfirmasi ke pelapor
 * saat teknisi menyerahkan pekerjaan, dan kabar ke teknisi saat penerima menjawab.
 */
final class PemberiTahuKonfirmasiPenerima
{
    public function __construct(
        private readonly LayananNotifikasi $notifikasi,
        private readonly AturanKonfirmasiPenerima $aturan,
    ) {}

    /** Pelapor keluhan asal diminta mengonfirmasi dari Mode Lapangan. */
    public function mintaPelapor(PerintahKerja $perintahKerja): void
    {
        if ($perintahKerja->KeluhanId === null) {
            return;
        }

        $keluhan = Keluhan::query()->withoutGlobalScope(ScopeLingkup::class)->find($perintahKerja->KeluhanId);
        $pelaporId = $keluhan?->PelaporId;

        // Teknisi yang melapor lewat aksi cepat lalu mengerjakannya sendiri tidak meminta dirinya sendiri,
        // dan pekerjaan yang sudah ditandatangani penerima di HP teknisi tidak perlu ditanyakan lagi.
        if (! $keluhan instanceof Keluhan
            || $pelaporId === null
            || $this->aturan->ditugaskan($perintahKerja, $pelaporId)
            || $this->aturan->berlaku($perintahKerja) !== null) {
            return;
        }

        $this->notifikasi->kirim(
            penggunaId: $pelaporId,
            jenisPeristiwa: 'Keluhan.MenungguKonfirmasi',
            isi: "Pekerjaan untuk laporan {$keluhan->Nomor} ({$keluhan->Judul}) sudah selesai dikerjakan. Cek hasilnya, lalu konfirmasi.",
            judul: 'Konfirmasi hasil perbaikan',
            jenisEntitas: 'Keluhan',
            entitasId: $keluhan->Id,
        );
    }

    public function masihBermasalah(PerintahKerja $perintahKerja, string $namaPenerima, string $alasan): void
    {
        $this->kirimKeTeknisi(
            $perintahKerja,
            'PerintahKerja.MasihBermasalah',
            'Pekerjaan dikembalikan penerima',
            "{$namaPenerima} menyatakan {$perintahKerja->Nomor} masih bermasalah: {$alasan}",
        );
    }

    public function diterima(PerintahKerja $perintahKerja, string $namaPenerima): void
    {
        $this->kirimKeTeknisi(
            $perintahKerja,
            'PerintahKerja.DikonfirmasiPenerima',
            'Pekerjaan diterima',
            "{$namaPenerima} menerima pekerjaan {$perintahKerja->Nomor}.",
        );
    }

    private function kirimKeTeknisi(PerintahKerja $perintahKerja, string $jenis, string $judul, string $isi): void
    {
        $teknisi = $perintahKerja->penugasan()
            ->whereIn('Status', [StatusPenugasanPerintahKerja::Ditugaskan->value, StatusPenugasanPerintahKerja::Diterima->value])
            ->pluck('PenggunaId')
            ->unique();

        foreach ($teknisi as $penggunaId) {
            $this->notifikasi->kirim(
                penggunaId: (string) $penggunaId,
                jenisPeristiwa: $jenis,
                isi: $isi,
                judul: $judul,
                jenisEntitas: 'PerintahKerja',
                entitasId: $perintahKerja->Id,
            );
        }
    }
}
