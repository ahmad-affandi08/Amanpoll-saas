<?php

declare(strict_types=1);

namespace App\Domain\Kalibrasi\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\HasilTitikUkurKalibrasi;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\JenisKalibrasi;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\PelaksanaanKalibrasi;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\RencanaKalibrasi;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\TitikUkurKalibrasi;
use App\Domain\Platform\Application\Services\LayananNomorDokumen;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use Carbon\Carbon;

final class KelolaPelaksanaanKalibrasi
{
    public function __construct(
        private readonly KonteksOrganisasi $konteksOrganisasi,
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananNomorDokumen $layananNomorDokumen,
        private readonly LayananAudit $layananAudit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function jadwalkan(array $data, string $penggunaId): PelaksanaanKalibrasi
    {
        return $this->transaksi->jalankan(function () use ($data, $penggunaId): PelaksanaanKalibrasi {
            $organisasiId = $this->konteksOrganisasi->wajibId();

            $aset = Aset::query()
                ->where('OrganisasiId', $organisasiId)
                ->findOrFail($data['AsetId']);

            $rencanaKalibrasi = null;
            if (! empty($data['RencanaKalibrasiId'])) {
                $rencanaKalibrasi = RencanaKalibrasi::query()
                    ->where('OrganisasiId', $organisasiId)
                    ->findOrFail($data['RencanaKalibrasiId']);
            }

            // Jika JenisKalibrasiId tidak diisi tetapi RencanaKalibrasi punya, ambil dari rencana
            $jenisKalibrasiId = $data['JenisKalibrasiId'] ?? $rencanaKalibrasi?->JenisKalibrasiId;
            $penyediaId = $data['PenyediaId'] ?? $rencanaKalibrasi?->PenyediaId;

            // Generate nomor dokumen pelaksanaan kalibrasi
            $nomor = $data['Nomor'] ?? null;
            if ($nomor === null || trim($nomor) === '') {
                try {
                    $nomor = $this->layananNomorDokumen->berikutnya($organisasiId, 'Kalibrasi');
                } catch (DataTidakDitemukan) {
                    $nomor = 'CAL-'.now()->format('Ymd').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
                }
            }

            $pelaksanaan = PelaksanaanKalibrasi::create([
                'OrganisasiId' => $organisasiId,
                'Nomor' => $nomor,
                'RencanaKalibrasiId' => $rencanaKalibrasi?->Id,
                'AsetId' => $aset->Id,
                'JenisKalibrasiId' => $jenisKalibrasiId,
                'PenyediaId' => $penyediaId,
                'PerintahKerjaId' => $data['PerintahKerjaId'] ?? null,
                'TanggalKalibrasi' => isset($data['TanggalKalibrasi']) ? Carbon::parse($data['TanggalKalibrasi'])->toDateString() : now()->toDateString(),
                'TanggalBerlakuSampai' => isset($data['TanggalBerlakuSampai']) ? Carbon::parse($data['TanggalBerlakuSampai'])->toDateString() : null,
                'Hasil' => $data['Hasil'] ?? 'Terjadwal',
                'NomorSertifikat' => $data['NomorSertifikat'] ?? null,
                'Laboratorium' => $data['Laboratorium'] ?? null,
                'KondisiLingkungan' => $data['KondisiLingkungan'] ?? null,
                'Catatan' => $data['Catatan'] ?? null,
                'DilaksanakanOleh' => $data['DilaksanakanOleh'] ?? $penggunaId,
            ]);

            // Salin titik ukur dari template JenisKalibrasi jika ada
            if ($jenisKalibrasiId !== null) {
                $templateTitikUkur = TitikUkurKalibrasi::query()
                    ->where('OrganisasiId', $organisasiId)
                    ->where('JenisKalibrasiId', $jenisKalibrasiId)
                    ->where('Aktif', true)
                    ->orderBy('Urutan')
                    ->get();

                foreach ($templateTitikUkur as $tu) {
                    HasilTitikUkurKalibrasi::create([
                        'OrganisasiId' => $organisasiId,
                        'PelaksanaanKalibrasiId' => $pelaksanaan->Id,
                        'TitikUkurKalibrasiId' => $tu->Id,
                        'NamaTitik' => $tu->Nama,
                        'NilaiReferensi' => $tu->NilaiReferensi,
                        'NilaiTerukur' => null,
                        'Koreksi' => null,
                        'Ketidakpastian' => null,
                        'Satuan' => $tu->Satuan,
                        'Hasil' => 'BelumDiuji',
                        'Catatan' => null,
                    ]);
                }
            }

            $this->layananAudit->catat(
                aksi: 'PelaksanaanKalibrasi.Dijadwalkan',
                jenisEntitas: 'PelaksanaanKalibrasi',
                entitasId: $pelaksanaan->Id,
                dataSesudah: $pelaksanaan->toArray(),
            );

            return $pelaksanaan;
        });
    }

    /**
     * Menyimpan hasil pengukuran setiap titik ukur dan melakukan evaluasi pass/fail otomatis (14.04).
     *
     * @param  list<array<string, mixed>>  $daftarHasil
     */
    public function simpanHasilTitikUkur(PelaksanaanKalibrasi $pelaksanaan, array $daftarHasil, string $penggunaId): void
    {
        $this->transaksi->jalankan(function () use ($pelaksanaan, $daftarHasil): void {
            $organisasiId = $this->konteksOrganisasi->wajibId();

            foreach ($daftarHasil as $item) {
                $hasilModel = null;
                if (! empty($item['Id'])) {
                    $hasilModel = HasilTitikUkurKalibrasi::query()
                        ->where('OrganisasiId', $organisasiId)
                        ->where('PelaksanaanKalibrasiId', $pelaksanaan->Id)
                        ->find($item['Id']);
                }

                $nilaiReferensi = isset($item['NilaiReferensi']) && $item['NilaiReferensi'] !== ''
                    ? (float) $item['NilaiReferensi']
                    : null;

                $nilaiTerukur = isset($item['NilaiTerukur']) && $item['NilaiTerukur'] !== ''
                    ? (float) $item['NilaiTerukur']
                    : null;

                // Hitung Koreksi: NilaiTerukur - NilaiReferensi
                $koreksi = ($nilaiTerukur !== null && $nilaiReferensi !== null)
                    ? ($nilaiTerukur - $nilaiReferensi)
                    : null;

                // Ambil toleransi dari template TitikUkur jika terhubung
                $toleransiMinus = isset($item['ToleransiMinus']) ? (float) $item['ToleransiMinus'] : null;
                $toleransiPlus = isset($item['ToleransiPlus']) ? (float) $item['ToleransiPlus'] : null;

                if (($toleransiMinus === null || $toleransiPlus === null) && ! empty($item['TitikUkurKalibrasiId'])) {
                    $tu = TitikUkurKalibrasi::find($item['TitikUkurKalibrasiId']);
                    if ($tu) {
                        $toleransiMinus ??= (float) $tu->ToleransiMinus;
                        $toleransiPlus ??= (float) $tu->ToleransiPlus;
                        $nilaiReferensi ??= (float) $tu->NilaiReferensi;
                    }
                }

                // Evaluasi otomatis Hasil (Pass/Fail)
                $hasil = $item['Hasil'] ?? null;
                if ($nilaiTerukur !== null && $nilaiReferensi !== null && ($toleransiMinus !== null || $toleransiPlus !== null)) {
                    $min = $nilaiReferensi - (float) ($toleransiMinus ?? 0);
                    $max = $nilaiReferensi + (float) ($toleransiPlus ?? 0);

                    $hasil = ($nilaiTerukur >= $min && $nilaiTerukur <= $max) ? 'Lolos' : 'Gagal';
                }

                $dataSimpan = [
                    'OrganisasiId' => $organisasiId,
                    'PelaksanaanKalibrasiId' => $pelaksanaan->Id,
                    'TitikUkurKalibrasiId' => $item['TitikUkurKalibrasiId'] ?? $hasilModel?->TitikUkurKalibrasiId,
                    'NamaTitik' => $item['NamaTitik'] ?? $hasilModel?->NamaTitik ?? 'Titik Ukur',
                    'NilaiReferensi' => $nilaiReferensi,
                    'NilaiTerukur' => $nilaiTerukur,
                    'Koreksi' => $koreksi,
                    'Ketidakpastian' => isset($item['Ketidakpastian']) && $item['Ketidakpastian'] !== '' ? (float) $item['Ketidakpastian'] : null,
                    'Satuan' => $item['Satuan'] ?? $hasilModel?->Satuan,
                    'Hasil' => $hasil ?? ($nilaiTerukur !== null ? 'Lolos' : 'BelumDiuji'),
                    'Catatan' => $item['Catatan'] ?? null,
                ];

                if ($hasilModel) {
                    $hasilModel->update($dataSimpan);
                } else {
                    HasilTitikUkurKalibrasi::create($dataSimpan);
                }
            }

            $this->layananAudit->catat(
                aksi: 'PelaksanaanKalibrasi.HasilTitikUkurDiperbarui',
                jenisEntitas: 'PelaksanaanKalibrasi',
                entitasId: $pelaksanaan->Id,
            );
        });
    }

    /**
     * Finalisasi pelaksanaan kalibrasi, pengesahan sertifikat, dan pembaruan konsisten Next Due (Gate 14).
     *
     * @param  array<string, mixed>  $data
     */
    public function finalisasi(PelaksanaanKalibrasi $pelaksanaan, array $data, string $penggunaId): PelaksanaanKalibrasi
    {
        return $this->transaksi->jalankan(function () use ($pelaksanaan, $data, $penggunaId): PelaksanaanKalibrasi {
            $hasil = $data['Hasil'] ?? 'Lolos';
            $nomorSertifikat = $data['NomorSertifikat'] ?? $pelaksanaan->NomorSertifikat;
            $laboratorium = $data['Laboratorium'] ?? $pelaksanaan->Laboratorium;
            $catatan = $data['Catatan'] ?? $pelaksanaan->Catatan;
            $kondisiLingkungan = $data['KondisiLingkungan'] ?? $pelaksanaan->KondisiLingkungan;

            $tanggalKalibrasi = isset($data['TanggalKalibrasi'])
                ? Carbon::parse($data['TanggalKalibrasi'])
                : Carbon::parse($pelaksanaan->TanggalKalibrasi);

            $tanggalBerlakuSampai = isset($data['TanggalBerlakuSampai']) && $data['TanggalBerlakuSampai'] !== ''
                ? Carbon::parse($data['TanggalBerlakuSampai'])
                : null;

            // GATE 14: Perbarui TanggalBerikutnya pada RencanaKalibrasi secara konsisten jika terhubung
            if ($pelaksanaan->RencanaKalibrasiId !== null) {
                $rencana = RencanaKalibrasi::find($pelaksanaan->RencanaKalibrasiId);
                if ($rencana && $rencana->IntervalHari > 0) {
                    $tanggalBerikutnya = (clone $tanggalKalibrasi)->addDays($rencana->IntervalHari);
                    $rencana->update([
                        'TanggalBerikutnya' => $tanggalBerikutnya->toDateString(),
                    ]);

                    // Jika TanggalBerlakuSampai pada pelaksanaan belum diatur, samakan dengan TanggalBerikutnya
                    $tanggalBerlakuSampai ??= $tanggalBerikutnya;
                }
            }

            $pelaksanaan->update([
                'TanggalKalibrasi' => $tanggalKalibrasi->toDateString(),
                'TanggalBerlakuSampai' => $tanggalBerlakuSampai?->toDateString(),
                'Hasil' => $hasil,
                'NomorSertifikat' => $nomorSertifikat,
                'Laboratorium' => $laboratorium,
                'KondisiLingkungan' => $kondisiLingkungan,
                'Catatan' => $catatan,
                'DiverifikasiOleh' => $penggunaId,
                'DiverifikasiPada' => now(),
            ]);

            $this->layananAudit->catat(
                aksi: 'PelaksanaanKalibrasi.Difinalisasi',
                jenisEntitas: 'PelaksanaanKalibrasi',
                entitasId: $pelaksanaan->Id,
                dataSesudah: $pelaksanaan->fresh()->toArray(),
            );

            return $pelaksanaan->fresh();
        });
    }

    public function hapus(PelaksanaanKalibrasi $pelaksanaan, string $penggunaId): void
    {
        $this->transaksi->jalankan(function () use ($pelaksanaan): void {
            if ($pelaksanaan->DiverifikasiPada !== null) {
                throw new AturanBisnisDilanggar('Pelaksanaan kalibrasi yang sudah diverifikasi/difinalisasi tidak dapat dihapus.');
            }

            $dataLama = $pelaksanaan->toArray();
            $pelaksanaan->hasilTitikUkur()->delete();
            $pelaksanaan->delete();

            $this->layananAudit->catat(
                aksi: 'PelaksanaanKalibrasi.Dihapus',
                jenisEntitas: 'PelaksanaanKalibrasi',
                entitasId: $pelaksanaan->Id,
                dataSebelum: $dataLama,
            );
        });
    }
}
