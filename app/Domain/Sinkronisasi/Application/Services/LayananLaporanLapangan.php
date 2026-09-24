<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Application\Services;

use App\Core\Idempotensi\LayananIdempotensi;
use App\Core\Izin\PemeriksaIzin;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Pemeliharaan\Application\Actions\BuatKeluhan;
use App\Domain\Pemeliharaan\Domain\Enums\UrgensiPelapor;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KategoriKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * Jalur tunggal laporan kerusakan Mode Lapangan (DESIGN §36.7 layar 04–08), dipakai
 * kiriman online (`LapanganPelaporLaporController`) maupun antrean offline
 * (`PenanganBuatKeluhanLapangan`).
 *
 * Penomoran, SLA, riwayat status, audit, dan notifikasi tetap milik `BuatKeluhan`.
 * Yang ditambahkan di sini hanya hal khas layar lapangan:
 * - urgensi berbahasa awam disimpan terstruktur di `Keluhan.UsulanUrgensi` sebagai
 *   usulan (PRD 8.20). Ia menjadi `Prioritas` hanya bila pelapornya memegang
 *   `Keluhan.Kelola`, aturan yang sama dengan dasbor (`KeluhanController::store`);
 *   pelapor biasa tidak pernah menentukan prioritas lewat jalur ini;
 * - lokasi dan aset wajib berada di lingkup pelapor;
 * - `KunciLaporan` dari perangkat membuat kiriman yang sama tidak pernah menjadi
 *   dua keluhan, baik lewat jalur online yang diulang maupun lewat antrean offline
 *   sesudah jawaban online hilang di jalan.
 */
final class LayananLaporanLapangan
{
    /** Rute semu untuk kunci idempotensi; sama bagi jalur online dan offline. */
    public const RUTE_IDEMPOTENSI = 'LAPANGAN Keluhan.Buat';

    public function __construct(
        private readonly BuatKeluhan $buatKeluhan,
        private readonly LayananIdempotensi $idempotensi,
        private readonly TransaksiDatabase $transaksi,
        private readonly PemeriksaIzin $izin,
        private readonly KonteksOrganisasi $konteks,
    ) {}

    /**
     * Aturan validasi laporan lapangan. Aturan milik keluhan dibaca dari
     * formulir dasbor supaya tidak ada dua versi.
     *
     * @param  array<string, mixed>  $aturanKeluhan  aturan `SimpanKeluhanRequest`
     * @return array<string, mixed>
     */
    public static function aturan(array $aturanKeluhan): array
    {
        unset($aturanKeluhan['Prioritas'], $aturanKeluhan['Lampiran'], $aturanKeluhan['Lampiran.*']);

        return [
            ...$aturanKeluhan,
            'Urgensi' => ['nullable', 'string', 'in:'.implode(',', array_column(UrgensiPelapor::cases(), 'value'))],
            'KunciLaporan' => ['required', 'string', 'max:64'],
        ];
    }

    /**
     * Pemeriksaan yang tidak dapat dinyatakan sebagai aturan formulir:
     * - lokasi dan aset wajib di lingkup pelapor. `exists` pada formulir hanya
     *   memeriksa organisasi; kueri model di sini ikut disaring `ScopeLingkup`;
     * - kategori yang mewajibkan aset (sama dengan `SimpanKeluhanRequest::after`).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, string> pesan per kolom; kosong bila laporan layak dibuat
     */
    public function pelanggaran(array $data): array
    {
        $pelanggaran = [];

        if (filled($data['LokasiId'] ?? null) && ! Lokasi::query()->whereKey($data['LokasiId'])->exists()) {
            $pelanggaran['LokasiId'] = 'Lokasi ini di luar area yang bisa kamu laporkan.';
        }

        if (filled($data['AsetId'] ?? null) && ! Aset::query()->whereKey($data['AsetId'])->exists()) {
            $pelanggaran['AsetId'] = 'Alat ini di luar area yang bisa kamu laporkan.';
        } elseif (blank($data['AsetId'] ?? null) && filled($data['KategoriKeluhanId'] ?? null)
            && KategoriKeluhan::query()->whereKey($data['KategoriKeluhanId'])->value('AsetWajib')) {
            $pelanggaran['AsetId'] = 'Aset wajib dipilih untuk kategori keluhan ini.';
        }

        return $pelanggaran;
    }

    /**
     * Membuat keluhan, atau mengembalikan keluhan yang sudah dibuat dengan kunci yang sama.
     *
     * @param  array<string, mixed>  $data  data tervalidasi dari `aturan()`
     * @return array{keluhan: Keluhan, baru: bool}
     */
    public function laporkan(array $data, Pengguna $pelapor): array
    {
        $organisasiId = $this->konteks->wajibId();
        $kunci = (string) $data['KunciLaporan'];

        return $this->transaksi->jalankan(function () use ($data, $pelapor, $organisasiId, $kunci): array {
            // Sidik jari dari kuncinya sendiri: kiriman online membawa foto, salinan
            // offline-nya tidak, tetapi keduanya laporan yang sama.
            $sebelumnya = $this->idempotensi->daftarkan($organisasiId, $kunci, self::RUTE_IDEMPOTENSI, hash('sha256', $kunci));

            if ($sebelumnya !== null) {
                return ['keluhan' => $this->keluhanTercatat($sebelumnya->Respons), 'baru' => false];
            }

            $kategori = KategoriKeluhan::query()->whereKey($data['KategoriKeluhanId'])->firstOrFail();
            if ($kategori->AsetWajib && blank($data['AsetId'] ?? null)) {
                throw new AturanBisnisDilanggar('Aset wajib dipilih untuk kategori keluhan ini.');
            }

            $keluhan = $this->buatKeluhan->jalankan($this->susunData($data, $pelapor), $pelapor->Id);

            $this->idempotensi->simpanRespons(
                $organisasiId,
                $kunci,
                self::RUTE_IDEMPOTENSI,
                new JsonResponse(['KeluhanId' => $keluhan->Id], 201),
            );

            return ['keluhan' => $keluhan, 'baru' => true];
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function susunData(array $data, Pengguna $pelapor): array
    {
        $urgensi = filled($data['Urgensi'] ?? null) ? UrgensiPelapor::from((string) $data['Urgensi']) : null;
        $lokasiId = $data['LokasiId'];

        if (filled($data['AsetId'] ?? null)) {
            $lokasiAset = Aset::query()->whereKey($data['AsetId'])->value('LokasiId');
            $lokasiId = is_string($lokasiAset) ? $lokasiAset : $lokasiId;
        }

        return [
            'KategoriKeluhanId' => $data['KategoriKeluhanId'],
            'AsetId' => filled($data['AsetId'] ?? null) ? $data['AsetId'] : null,
            'LokasiId' => $lokasiId,
            'Judul' => $data['Judul'],
            'Deskripsi' => $data['Deskripsi'],
            'UsulanUrgensi' => $urgensi?->value,
            'Prioritas' => $urgensi !== null && $this->izin->boleh($pelapor->Id, 'Keluhan.Kelola')
                ? $urgensi->prioritas()->value
                : null,
            'Sumber' => 'Lapangan',
        ];
    }

    private function keluhanTercatat(?string $respons): Keluhan
    {
        $isi = json_decode((string) $respons, true);
        $keluhanId = is_array($isi) && is_string($isi['KeluhanId'] ?? null) ? $isi['KeluhanId'] : null;

        if ($keluhanId === null) {
            // Kiriman pertama dengan kunci ini belum tuntas; perangkat mencoba lagi nanti.
            throw new RuntimeException('Laporan dengan kunci ini masih diproses.');
        }

        return Keluhan::query()->findOrFail($keluhanId);
    }
}
