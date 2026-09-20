<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\ButirTemplatDaftarPeriksa;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\TemplatDaftarPeriksa;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

final class KelolaTemplatDaftarPeriksa
{
    public function __construct(
        private readonly KonteksOrganisasi $konteksOrganisasi,
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananAudit $layananAudit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function buat(array $data, string $penggunaId): TemplatDaftarPeriksa
    {
        return $this->transaksi->jalankan(function () use ($data): TemplatDaftarPeriksa {
            $organisasiId = $this->konteksOrganisasi->wajibId();

            $adaKode = TemplatDaftarPeriksa::query()
                ->where('OrganisasiId', $organisasiId)
                ->where('Kode', $data['Kode'])
                ->exists();

            if ($adaKode) {
                throw new AturanBisnisDilanggar("Templat daftar periksa dengan kode '{$data['Kode']}' sudah ada.");
            }

            $templat = TemplatDaftarPeriksa::create([
                'OrganisasiId' => $organisasiId,
                'Kode' => $data['Kode'],
                'Nama' => $data['Nama'],
                'Jenis' => $data['Jenis'] ?? 'Pemeliharaan',
                'KategoriAsetId' => $data['KategoriAsetId'] ?? null,
                'ModelAsetId' => $data['ModelAsetId'] ?? null,
                'VersiTemplat' => $data['VersiTemplat'] ?? 1,
                'Aktif' => $data['Aktif'] ?? true,
            ]);

            $this->layananAudit->catat(
                aksi: 'TemplatDaftarPeriksa.Dibuat',
                jenisEntitas: 'TemplatDaftarPeriksa',
                entitasId: $templat->Id,
                dataSesudah: $templat->toArray(),
            );

            return $templat;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function perbarui(TemplatDaftarPeriksa $templat, array $data, string $penggunaId): TemplatDaftarPeriksa
    {
        return $this->transaksi->jalankan(function () use ($templat, $data): TemplatDaftarPeriksa {
            $sebelum = $templat->toArray();

            if (isset($data['Kode']) && $data['Kode'] !== $templat->Kode) {
                $adaKode = TemplatDaftarPeriksa::query()
                    ->where('OrganisasiId', $templat->OrganisasiId)
                    ->where('Kode', $data['Kode'])
                    ->where('Id', '!=', $templat->Id)
                    ->exists();

                if ($adaKode) {
                    throw new AturanBisnisDilanggar("Templat daftar periksa dengan kode '{$data['Kode']}' sudah ada.");
                }
            }

            $templat->update([
                'Kode' => $data['Kode'] ?? $templat->Kode,
                'Nama' => $data['Nama'] ?? $templat->Nama,
                'Jenis' => $data['Jenis'] ?? $templat->Jenis,
                'KategoriAsetId' => array_key_exists('KategoriAsetId', $data) ? $data['KategoriAsetId'] : $templat->KategoriAsetId,
                'ModelAsetId' => array_key_exists('ModelAsetId', $data) ? $data['ModelAsetId'] : $templat->ModelAsetId,
                'Aktif' => $data['Aktif'] ?? $templat->Aktif,
            ]);

            $this->layananAudit->catat(
                aksi: 'TemplatDaftarPeriksa.Diperbarui',
                jenisEntitas: 'TemplatDaftarPeriksa',
                entitasId: $templat->Id,
                dataSebelum: $sebelum,
                dataSesudah: $templat->fresh()->toArray(),
            );

            return $templat;
        });
    }

    /**
     * Membuat versi baru dari templat dan menyalin semua butir pertanyaan.
     */
    public function buatVersiBaru(TemplatDaftarPeriksa $templatLama, string $penggunaId): TemplatDaftarPeriksa
    {
        return $this->transaksi->jalankan(function () use ($templatLama): TemplatDaftarPeriksa {
            $versiBaru = $templatLama->VersiTemplat + 1;
            $kodeBaru = $templatLama->Kode.'-v'.$versiBaru;

            $templatBaru = TemplatDaftarPeriksa::create([
                'OrganisasiId' => $templatLama->OrganisasiId,
                'Kode' => $kodeBaru,
                'Nama' => $templatLama->Nama.' (v'.$versiBaru.')',
                'Jenis' => $templatLama->Jenis,
                'KategoriAsetId' => $templatLama->KategoriAsetId,
                'ModelAsetId' => $templatLama->ModelAsetId,
                'VersiTemplat' => $versiBaru,
                'Aktif' => true,
            ]);

            $butirList = ButirTemplatDaftarPeriksa::query()
                ->where('TemplatDaftarPeriksaId', $templatLama->Id)
                ->orderBy('Urutan')
                ->get();

            foreach ($butirList as $b) {
                ButirTemplatDaftarPeriksa::create([
                    'OrganisasiId' => $templatBaru->OrganisasiId,
                    'TemplatDaftarPeriksaId' => $templatBaru->Id,
                    'Urutan' => $b->Urutan,
                    'Kode' => $b->Kode,
                    'Pertanyaan' => $b->Pertanyaan,
                    'TipeJawaban' => $b->TipeJawaban,
                    'Satuan' => $b->Satuan,
                    'Wajib' => $b->Wajib,
                    'NilaiMinimum' => $b->NilaiMinimum,
                    'NilaiMaksimum' => $b->NilaiMaksimum,
                    'Pilihan' => $b->Pilihan,
                    'BuktiFotoWajib' => $b->BuktiFotoWajib,
                    'MemicuTemuanJika' => $b->MemicuTemuanJika,
                ]);
            }

            $this->layananAudit->catat(
                aksi: 'TemplatDaftarPeriksa.VersiBaruDibuat',
                jenisEntitas: 'TemplatDaftarPeriksa',
                entitasId: $templatBaru->Id,
                dataSesudah: $templatBaru->toArray(),
            );

            return $templatBaru;
        });
    }

    public function hapus(TemplatDaftarPeriksa $templat, string $penggunaId): void
    {
        $this->transaksi->jalankan(function () use ($templat): void {
            if ($templat->pelaksanaan()->exists()) {
                throw new AturanBisnisDilanggar('Templat tidak dapat dihapus karena sudah memiliki riwayat pelaksanaan.');
            }

            $sebelum = $templat->toArray();
            $templat->butir()->delete();
            $templat->delete();

            $this->layananAudit->catat(
                aksi: 'TemplatDaftarPeriksa.Dihapus',
                jenisEntitas: 'TemplatDaftarPeriksa',
                entitasId: $templat->Id,
                dataSebelum: $sebelum,
            );
        });
    }
}
