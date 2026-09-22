<?php

declare(strict_types=1);

namespace App\Domain\Kalibrasi\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\JenisKalibrasi;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\TitikUkurKalibrasi;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

final class KelolaJenisKalibrasi
{
    public function __construct(
        private readonly KonteksOrganisasi $konteksOrganisasi,
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananAudit $layananAudit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function buat(array $data, string $penggunaId): JenisKalibrasi
    {
        return $this->transaksi->jalankan(function () use ($data): JenisKalibrasi {
            $organisasiId = $this->konteksOrganisasi->wajibId();

            $ada = filled($data['Kode'] ?? null) && JenisKalibrasi::query()
                ->where('OrganisasiId', $organisasiId)
                ->where('Kode', $data['Kode'])
                ->exists();

            if ($ada) {
                throw new AturanBisnisDilanggar("Jenis kalibrasi dengan kode '{$data['Kode']}' sudah terdaftar.");
            }

            $jenis = JenisKalibrasi::create([
                'OrganisasiId' => $organisasiId,
                'Kode' => $data['Kode'] ?? null,
                'Nama' => $data['Nama'],
                'Deskripsi' => $data['Deskripsi'] ?? null,
                'Aktif' => $data['Aktif'] ?? true,
            ]);

            $this->layananAudit->catat(
                aksi: 'JenisKalibrasi.Dibuat',
                jenisEntitas: 'JenisKalibrasi',
                entitasId: $jenis->Id,
                dataSesudah: $jenis->toArray(),
            );

            return $jenis;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function perbarui(JenisKalibrasi $jenis, array $data, string $penggunaId): JenisKalibrasi
    {
        return $this->transaksi->jalankan(function () use ($jenis, $data): JenisKalibrasi {
            $organisasiId = $this->konteksOrganisasi->wajibId();

            if (isset($data['Kode']) && $data['Kode'] !== $jenis->Kode) {
                $ada = JenisKalibrasi::query()
                    ->where('OrganisasiId', $organisasiId)
                    ->where('Kode', $data['Kode'])
                    ->where('Id', '!=', $jenis->Id)
                    ->exists();

                if ($ada) {
                    throw new AturanBisnisDilanggar("Jenis kalibrasi dengan kode '{$data['Kode']}' sudah digunakan.");
                }
            }

            $dataLama = $jenis->toArray();

            $jenis->update([
                'Kode' => $data['Kode'] ?? $jenis->Kode,
                'Nama' => $data['Nama'] ?? $jenis->Nama,
                'Deskripsi' => array_key_exists('Deskripsi', $data) ? $data['Deskripsi'] : $jenis->Deskripsi,
                'Aktif' => $data['Aktif'] ?? $jenis->Aktif,
            ]);

            $this->layananAudit->catat(
                aksi: 'JenisKalibrasi.Diperbarui',
                jenisEntitas: 'JenisKalibrasi',
                entitasId: $jenis->Id,
                dataSebelum: $dataLama,
                dataSesudah: $jenis->fresh()->toArray(),
            );

            return $jenis->fresh();
        });
    }

    public function hapus(JenisKalibrasi $jenis, string $penggunaId): void
    {
        $this->transaksi->jalankan(function () use ($jenis): void {
            if ($jenis->rencanaKalibrasi()->exists() || $jenis->pelaksanaanKalibrasi()->exists()) {
                throw new AturanBisnisDilanggar('Jenis kalibrasi tidak dapat dihapus karena sudah memiliki rencana atau riwayat pelaksanaan kalibrasi.');
            }

            $dataLama = $jenis->toArray();

            // Hapus titik ukur terkait jika ada
            $jenis->titikUkur()->delete();
            $jenis->delete();

            $this->layananAudit->catat(
                aksi: 'JenisKalibrasi.Dihapus',
                jenisEntitas: 'JenisKalibrasi',
                entitasId: $jenis->Id,
                dataSebelum: $dataLama,
            );
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function tambahTitikUkur(JenisKalibrasi $jenis, array $data, string $penggunaId): TitikUkurKalibrasi
    {
        return $this->transaksi->jalankan(function () use ($jenis, $data): TitikUkurKalibrasi {
            $organisasiId = $this->konteksOrganisasi->wajibId();

            $titikUkur = TitikUkurKalibrasi::create([
                'OrganisasiId' => $organisasiId,
                'JenisKalibrasiId' => $jenis->Id,
                'KategoriAsetId' => $data['KategoriAsetId'] ?? null,
                'Nama' => $data['Nama'],
                'Satuan' => $data['Satuan'] ?? null,
                'NilaiReferensi' => $data['NilaiReferensi'] ?? null,
                'ToleransiMinus' => $data['ToleransiMinus'] ?? 0,
                'ToleransiPlus' => $data['ToleransiPlus'] ?? 0,
                'Urutan' => $data['Urutan'] ?? (($jenis->titikUkur()->max('Urutan') ?? 0) + 1),
                'Aktif' => $data['Aktif'] ?? true,
            ]);

            $this->layananAudit->catat(
                aksi: 'TitikUkurKalibrasi.Dibuat',
                jenisEntitas: 'TitikUkurKalibrasi',
                entitasId: $titikUkur->Id,
                dataSesudah: $titikUkur->toArray(),
            );

            return $titikUkur;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function perbaruiTitikUkur(TitikUkurKalibrasi $titikUkur, array $data, string $penggunaId): TitikUkurKalibrasi
    {
        return $this->transaksi->jalankan(function () use ($titikUkur, $data): TitikUkurKalibrasi {
            $dataLama = $titikUkur->toArray();

            $titikUkur->update([
                'Nama' => $data['Nama'] ?? $titikUkur->Nama,
                'Satuan' => array_key_exists('Satuan', $data) ? $data['Satuan'] : $titikUkur->Satuan,
                'NilaiReferensi' => array_key_exists('NilaiReferensi', $data) ? $data['NilaiReferensi'] : $titikUkur->NilaiReferensi,
                'ToleransiMinus' => array_key_exists('ToleransiMinus', $data) ? $data['ToleransiMinus'] : $titikUkur->ToleransiMinus,
                'ToleransiPlus' => array_key_exists('ToleransiPlus', $data) ? $data['ToleransiPlus'] : $titikUkur->ToleransiPlus,
                'Urutan' => $data['Urutan'] ?? $titikUkur->Urutan,
                'Aktif' => $data['Aktif'] ?? $titikUkur->Aktif,
            ]);

            $this->layananAudit->catat(
                aksi: 'TitikUkurKalibrasi.Diperbarui',
                jenisEntitas: 'TitikUkurKalibrasi',
                entitasId: $titikUkur->Id,
                dataSebelum: $dataLama,
                dataSesudah: $titikUkur->fresh()->toArray(),
            );

            return $titikUkur->fresh();
        });
    }

    public function hapusTitikUkur(TitikUkurKalibrasi $titikUkur, string $penggunaId): void
    {
        $this->transaksi->jalankan(function () use ($titikUkur): void {
            $dataLama = $titikUkur->toArray();
            $titikUkur->delete();

            $this->layananAudit->catat(
                aksi: 'TitikUkurKalibrasi.Dihapus',
                jenisEntitas: 'TitikUkurKalibrasi',
                entitasId: $titikUkur->Id,
                dataSebelum: $dataLama,
            );
        });
    }
}
