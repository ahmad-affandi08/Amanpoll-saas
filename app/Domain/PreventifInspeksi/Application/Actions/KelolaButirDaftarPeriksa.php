<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\ButirTemplatDaftarPeriksa;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\TemplatDaftarPeriksa;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

final class KelolaButirDaftarPeriksa
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananAudit $layananAudit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function simpan(TemplatDaftarPeriksa $templat, array $data, ?string $butirId = null): ButirTemplatDaftarPeriksa
    {
        return $this->transaksi->jalankan(function () use ($templat, $data, $butirId): ButirTemplatDaftarPeriksa {
            if ($butirId !== null) {
                $butir = ButirTemplatDaftarPeriksa::query()
                    ->where('TemplatDaftarPeriksaId', $templat->Id)
                    ->findOrFail($butirId);

                $sebelum = $butir->toArray();

                $butir->update([
                    'Urutan' => $data['Urutan'] ?? $butir->Urutan,
                    'Kode' => $data['Kode'] ?? $butir->Kode,
                    'Pertanyaan' => $data['Pertanyaan'] ?? $butir->Pertanyaan,
                    'TipeJawaban' => $data['TipeJawaban'] ?? $butir->TipeJawaban,
                    'Satuan' => array_key_exists('Satuan', $data) ? $data['Satuan'] : $butir->Satuan,
                    'Wajib' => $data['Wajib'] ?? $butir->Wajib,
                    'NilaiMinimum' => array_key_exists('NilaiMinimum', $data) ? $data['NilaiMinimum'] : $butir->NilaiMinimum,
                    'NilaiMaksimum' => array_key_exists('NilaiMaksimum', $data) ? $data['NilaiMaksimum'] : $butir->NilaiMaksimum,
                    'Pilihan' => array_key_exists('Pilihan', $data) ? $data['Pilihan'] : $butir->Pilihan,
                    'BuktiFotoWajib' => $data['BuktiFotoWajib'] ?? $butir->BuktiFotoWajib,
                    'MemicuTemuanJika' => array_key_exists('MemicuTemuanJika', $data) ? $data['MemicuTemuanJika'] : $butir->MemicuTemuanJika,
                ]);

                $this->layananAudit->catat(
                    aksi: 'ButirTemplatDaftarPeriksa.Diperbarui',
                    jenisEntitas: 'ButirTemplatDaftarPeriksa',
                    entitasId: $butir->Id,
                    dataSebelum: $sebelum,
                    dataSesudah: $butir->fresh()->toArray(),
                );

                return $butir;
            }

            $urutanMaks = (int) ButirTemplatDaftarPeriksa::query()
                ->where('TemplatDaftarPeriksaId', $templat->Id)
                ->max('Urutan');

            $butir = ButirTemplatDaftarPeriksa::create([
                'OrganisasiId' => $templat->OrganisasiId,
                'TemplatDaftarPeriksaId' => $templat->Id,
                'Urutan' => $data['Urutan'] ?? ($urutanMaks + 1),
                'Kode' => $data['Kode'] ?? null,
                'Pertanyaan' => $data['Pertanyaan'],
                'TipeJawaban' => $data['TipeJawaban'] ?? 'Teks',
                'Satuan' => $data['Satuan'] ?? null,
                'Wajib' => $data['Wajib'] ?? false,
                'NilaiMinimum' => $data['NilaiMinimum'] ?? null,
                'NilaiMaksimum' => $data['NilaiMaksimum'] ?? null,
                'Pilihan' => $data['Pilihan'] ?? null,
                'BuktiFotoWajib' => $data['BuktiFotoWajib'] ?? false,
                'MemicuTemuanJika' => $data['MemicuTemuanJika'] ?? null,
            ]);

            $this->layananAudit->catat(
                aksi: 'ButirTemplatDaftarPeriksa.Dibuat',
                jenisEntitas: 'ButirTemplatDaftarPeriksa',
                entitasId: $butir->Id,
                dataSesudah: $butir->toArray(),
            );

            return $butir;
        });
    }

    public function hapus(ButirTemplatDaftarPeriksa $butir): void
    {
        $this->transaksi->jalankan(function () use ($butir): void {
            if ($butir->templatDaftarPeriksa->pelaksanaan()->where('Status', 'Selesai')->exists()) {
                throw new AturanBisnisDilanggar('Butir tidak dapat dihapus karena sudah digunakan dalam pelaksanaan daftar periksa yang telah selesai.');
            }

            $sebelum = $butir->toArray();
            $butir->delete();

            $this->layananAudit->catat(
                aksi: 'ButirTemplatDaftarPeriksa.Dihapus',
                jenisEntitas: 'ButirTemplatDaftarPeriksa',
                entitasId: $butir->Id,
                dataSebelum: $sebelum,
            );
        });
    }

    /**
     * @param  array<int, string>  $urutanIds
     */
    public function urutkanUlang(TemplatDaftarPeriksa $templat, array $urutanIds): void
    {
        $this->transaksi->jalankan(function () use ($templat, $urutanIds): void {
            foreach ($urutanIds as $index => $id) {
                ButirTemplatDaftarPeriksa::query()
                    ->where('TemplatDaftarPeriksaId', $templat->Id)
                    ->where('Id', $id)
                    ->update(['Urutan' => $index + 1]);
            }
        });
    }
}
