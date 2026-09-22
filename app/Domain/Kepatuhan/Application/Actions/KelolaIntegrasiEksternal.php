<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Kepatuhan\Domain\Enums\StatusIntegrasiEksternal;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\IntegrasiEksternal;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/** Konfigurasi integrasi eksternal (19.01). */
final class KelolaIntegrasiEksternal
{
    public function __construct(
        private readonly KonteksOrganisasi $konteks,
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananAudit $audit,
    ) {}

    /** @param array<string, mixed> $data */
    public function buat(array $data): IntegrasiEksternal
    {
        return $this->transaksi->jalankan(function () use ($data): IntegrasiEksternal {
            $integrasi = IntegrasiEksternal::create([
                'OrganisasiId' => $this->konteks->wajibId(),
                'Kode' => $data['Kode'],
                'Nama' => $data['Nama'],
                'Jenis' => $data['Jenis'],
                'UrlDasar' => $data['UrlDasar'] ?? null,
                'MetodeAutentikasi' => $data['MetodeAutentikasi'] ?? null,
                'KonfigurasiTerenkripsi' => $data['Konfigurasi'] ?? null,
                'Status' => StatusIntegrasiEksternal::Aktif->value,
            ]);

            // Audit sengaja tidak memuat kredensial, hanya nama kuncinya.
            $this->audit->catat('IntegrasiEksternal.Dibuat', 'IntegrasiEksternal', $integrasi->Id, dataSesudah: [
                'Kode' => $integrasi->Kode,
                'Nama' => $integrasi->Nama,
                'Jenis' => $integrasi->Jenis,
                'KunciKonfigurasi' => array_keys($data['Konfigurasi'] ?? []),
            ]);

            return $integrasi;
        });
    }

    /** @param array<string, mixed> $data */
    public function ubah(IntegrasiEksternal $integrasi, array $data): IntegrasiEksternal
    {
        $sebelum = ['Kode' => $integrasi->Kode, 'Nama' => $integrasi->Nama, 'Status' => $integrasi->Status];

        $integrasi->fill([
            'Kode' => $data['Kode'],
            'Nama' => $data['Nama'],
            'Jenis' => $data['Jenis'],
            'UrlDasar' => $data['UrlDasar'] ?? null,
            'MetodeAutentikasi' => $data['MetodeAutentikasi'] ?? null,
        ]);

        // Konfigurasi hanya ditimpa bila klien benar-benar mengirim nilai baru.
        if (array_key_exists('Konfigurasi', $data) && $data['Konfigurasi'] !== null) {
            $integrasi->KonfigurasiTerenkripsi = $data['Konfigurasi'];
        }

        $integrasi->save();
        $this->audit->catat('IntegrasiEksternal.Diubah', 'IntegrasiEksternal', $integrasi->Id, dataSebelum: $sebelum, dataSesudah: [
            'Kode' => $integrasi->Kode,
            'Nama' => $integrasi->Nama,
            'Status' => $integrasi->Status,
        ]);

        return $integrasi->refresh();
    }

    public function ubahStatus(IntegrasiEksternal $integrasi, string $status): IntegrasiEksternal
    {
        if (StatusIntegrasiEksternal::tryFrom($status) === null) {
            throw new AturanBisnisDilanggar('Status integrasi tidak dikenal.');
        }

        $integrasi->Status = $status;
        $integrasi->save();
        $this->audit->catat('IntegrasiEksternal.StatusDiubah', 'IntegrasiEksternal', $integrasi->Id, dataSesudah: ['Status' => $status]);

        return $integrasi->refresh();
    }

    public function hapus(IntegrasiEksternal $integrasi): void
    {
        if ($integrasi->pemetaan()->exists()) {
            throw new AturanBisnisDilanggar('Integrasi yang masih memiliki pemetaan data tidak dapat dihapus.');
        }

        $id = $integrasi->Id;
        $this->transaksi->jalankan(function () use ($integrasi, $id): void {
            $integrasi->sinkronisasi()->delete();
            $integrasi->delete();
            $this->audit->catat('IntegrasiEksternal.Dihapus', 'IntegrasiEksternal', $id);
        });
    }
}
