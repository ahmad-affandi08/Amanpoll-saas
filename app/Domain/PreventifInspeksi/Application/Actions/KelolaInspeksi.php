<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Core\Organisasi\KalenderOrganisasi;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Pemeliharaan\Application\Actions\BuatPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Platform\Application\Services\LayananNomorDokumen;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\Inspeksi;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\PelaksanaanDaftarPeriksa;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\TemplatInspeksi;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;

final class KelolaInspeksi
{
    public function __construct(
        private readonly KonteksOrganisasi $konteksOrganisasi,
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananNomorDokumen $layananNomorDokumen,
        private readonly BuatPerintahKerja $buatPerintahKerja,
        private readonly LayananAudit $layananAudit,
        private readonly KalenderOrganisasi $kalender,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function buatTemplat(array $data, string $penggunaId): TemplatInspeksi
    {
        return $this->transaksi->jalankan(function () use ($data): TemplatInspeksi {
            $organisasiId = $this->konteksOrganisasi->wajibId();

            $ada = filled($data['Kode'] ?? null) && TemplatInspeksi::query()
                ->where('OrganisasiId', $organisasiId)
                ->where('Kode', $data['Kode'])
                ->exists();

            if ($ada) {
                throw new AturanBisnisDilanggar("Templat inspeksi dengan kode '{$data['Kode']}' sudah ada.");
            }

            $templat = TemplatInspeksi::create([
                'OrganisasiId' => $organisasiId,
                'Kode' => $data['Kode'] ?? null,
                'Nama' => $data['Nama'],
                'KategoriAsetId' => $data['KategoriAsetId'] ?? null,
                'TemplatDaftarPeriksaId' => $data['TemplatDaftarPeriksaId'] ?? null,
                'IntervalHari' => $data['IntervalHari'] ?? 30,
                'Aktif' => $data['Aktif'] ?? true,
            ]);

            $this->layananAudit->catat(
                aksi: 'TemplatInspeksi.Dibuat',
                jenisEntitas: 'TemplatInspeksi',
                entitasId: $templat->Id,
                dataSesudah: $templat->toArray(),
            );

            return $templat;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function perbaruiTemplat(TemplatInspeksi $templat, array $data, string $penggunaId): TemplatInspeksi
    {
        return $this->transaksi->jalankan(function () use ($templat, $data): TemplatInspeksi {
            $sebelum = $templat->toArray();

            if (isset($data['Kode']) && $data['Kode'] !== $templat->Kode) {
                $ada = TemplatInspeksi::query()
                    ->where('OrganisasiId', $templat->OrganisasiId)
                    ->where('Kode', $data['Kode'])
                    ->where('Id', '!=', $templat->Id)
                    ->exists();

                if ($ada) {
                    throw new AturanBisnisDilanggar("Templat inspeksi dengan kode '{$data['Kode']}' sudah ada.");
                }
            }

            $templat->update([
                'Kode' => $data['Kode'] ?? $templat->Kode,
                'Nama' => $data['Nama'] ?? $templat->Nama,
                'KategoriAsetId' => array_key_exists('KategoriAsetId', $data) ? $data['KategoriAsetId'] : $templat->KategoriAsetId,
                'TemplatDaftarPeriksaId' => array_key_exists('TemplatDaftarPeriksaId', $data) ? $data['TemplatDaftarPeriksaId'] : $templat->TemplatDaftarPeriksaId,
                'IntervalHari' => $data['IntervalHari'] ?? $templat->IntervalHari,
                'Aktif' => $data['Aktif'] ?? $templat->Aktif,
            ]);

            $this->layananAudit->catat(
                aksi: 'TemplatInspeksi.Diperbarui',
                jenisEntitas: 'TemplatInspeksi',
                entitasId: $templat->Id,
                dataSebelum: $sebelum,
                dataSesudah: $templat->fresh()->toArray(),
            );

            return $templat;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function jadwalkan(array $data, string $penggunaId): Inspeksi
    {
        return $this->transaksi->jalankan(function () use ($data): Inspeksi {
            $organisasiId = $this->konteksOrganisasi->wajibId();

            $templat = TemplatInspeksi::query()
                ->where('OrganisasiId', $organisasiId)
                ->findOrFail($data['TemplatInspeksiId']);

            $aset = Aset::query()
                ->where('OrganisasiId', $organisasiId)
                ->findOrFail($data['AsetId']);

            $nomor = $data['Nomor'] ?? null;
            if ($nomor === null) {
                try {
                    $nomor = $this->layananNomorDokumen->berikutnya($organisasiId, 'Inspeksi');
                } catch (DataTidakDitemukan) {
                    $nomor = 'INSP-'.$this->kalender->sekarang($organisasiId)->format('Ymd').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
                }
            }

            $pelaksanaanId = null;
            if ($templat->TemplatDaftarPeriksaId !== null) {
                $pelaksanaan = PelaksanaanDaftarPeriksa::create([
                    'OrganisasiId' => $organisasiId,
                    'TemplatDaftarPeriksaId' => $templat->TemplatDaftarPeriksaId,
                    'AsetId' => $aset->Id,
                    'DilaksanakanOleh' => $data['DilaksanakanOleh'] ?? null,
                    'Status' => 'Draft',
                ]);
                $pelaksanaanId = $pelaksanaan->Id;
            }

            $inspeksi = Inspeksi::create([
                'OrganisasiId' => $organisasiId,
                'Nomor' => $nomor,
                'TemplatInspeksiId' => $templat->Id,
                'AsetId' => $aset->Id,
                'PelaksanaanDaftarPeriksaId' => $pelaksanaanId,
                'DijadwalkanPada' => $data['DijadwalkanPada'] ?? now(),
                'Status' => 'Terjadwal',
                'DilaksanakanOleh' => $data['DilaksanakanOleh'] ?? null,
            ]);

            $this->layananAudit->catat(
                aksi: 'Inspeksi.Dijadwalkan',
                jenisEntitas: 'Inspeksi',
                entitasId: $inspeksi->Id,
                dataSesudah: $inspeksi->toArray(),
            );

            return $inspeksi;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function laksanakan(Inspeksi $inspeksi, array $data, string $penggunaId): Inspeksi
    {
        return $this->transaksi->jalankan(function () use ($inspeksi, $data, $penggunaId): Inspeksi {
            /** @var Inspeksi $terkunci */
            $terkunci = Inspeksi::query()->lockForUpdate()->findOrFail($inspeksi->Id);

            if ($terkunci->Status === 'Selesai') {
                throw new AturanBisnisDilanggar('Inspeksi sudah selesai dan tidak dapat diubah lagi.');
            }

            $sebelum = $terkunci->toArray();

            $terkunci->update([
                'DilaksanakanPada' => $data['DilaksanakanPada'] ?? now(),
                'DilaksanakanOleh' => $penggunaId,
                'Hasil' => $data['Hasil'], // 'Lolos', 'PerluPerhatian', 'Gagal'
                'Temuan' => $data['Temuan'] ?? null,
                'TindakLanjut' => $data['TindakLanjut'] ?? null,
                'Status' => 'Selesai',
            ]);

            $this->layananAudit->catat(
                aksi: 'Inspeksi.Dilaksanakan',
                jenisEntitas: 'Inspeksi',
                entitasId: $terkunci->Id,
                dataSebelum: $sebelum,
                dataSesudah: $terkunci->fresh()->toArray(),
            );

            return $terkunci;
        });
    }

    /**
     * @param  array<string, mixed>  $dataTambahan
     */
    public function buatPerintahKerjaKorektif(Inspeksi $inspeksi, array $dataTambahan, string $penggunaId): PerintahKerja
    {
        return $this->transaksi->jalankan(function () use ($inspeksi, $dataTambahan, $penggunaId): PerintahKerja {
            /** @var Inspeksi $terkunci */
            $terkunci = Inspeksi::query()->lockForUpdate()->with('aset')->findOrFail($inspeksi->Id);

            if ($terkunci->PerintahKerjaId !== null) {
                throw new AturanBisnisDilanggar('Perintah kerja korektif untuk inspeksi ini sudah pernah dibuat.');
            }

            $aset = $terkunci->aset;
            $judul = $dataTambahan['Judul'] ?? "Tindak Lanjut Inspeksi {$terkunci->Nomor}";
            $deskripsi = $dataTambahan['Deskripsi'] ?? ($terkunci->Temuan ?: 'Temuan inspeksi memerlukan tindakan perbaikan.');

            $perintahKerja = $this->buatPerintahKerja->jalankan([
                'OrganisasiId' => $terkunci->OrganisasiId,
                'Jenis' => 'Korektif',
                'Judul' => $judul,
                'Deskripsi' => $deskripsi,
                'Prioritas' => $dataTambahan['Prioritas'] ?? 'Tinggi',
                'LokasiId' => $aset?->LokasiId,
                'AsetIds' => [$terkunci->AsetId],
            ], $penggunaId);

            $terkunci->update([
                'PerintahKerjaId' => $perintahKerja->Id,
                'TindakLanjut' => ($terkunci->TindakLanjut ? $terkunci->TindakLanjut."\n" : '')."Dibuat Perintah Kerja Korektif: {$perintahKerja->Nomor}",
            ]);

            $this->layananAudit->catat(
                aksi: 'Inspeksi.PerintahKerjaKorektifDibuat',
                jenisEntitas: 'Inspeksi',
                entitasId: $terkunci->Id,
                dataSesudah: [
                    'InspeksiId' => $terkunci->Id,
                    'PerintahKerjaId' => $perintahKerja->Id,
                ],
            );

            return $perintahKerja;
        });
    }
}
