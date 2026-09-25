<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Platform\Application\Services\KatalogPenyediaLayanan;
use App\Domain\Platform\Application\Services\PenyusunKredensialPenyedia;
use App\Domain\Platform\Domain\Enums\KategoriPenyediaLayanan;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenyediaLayananOrganisasi;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;

/**
 * Menyimpan email atau WhatsApp milik organisasi untuk notifikasi stafnya (PRD 8.23).
 *
 * Aturannya mengikuti konsol platform: rahasia kosong mempertahankan nilai lama dan
 * penyedia hanya aktif bila isian wajibnya lengkap. Satu organisasi hanya memakai satu
 * penyedia per kategori, jadi mengaktifkan satu menonaktifkan yang lain. Kredensial yang
 * berganti menghapus catatan galat lama, karena galat itu milik kredensial sebelumnya.
 */
final class SimpanPenyediaLayananOrganisasi
{
    public function __construct(
        private readonly KatalogPenyediaLayanan $katalog,
        private readonly PenyusunKredensialPenyedia $penyusun,
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananAudit $audit,
    ) {}

    /**
     * @param  array{Aktif?: bool, ModeUji?: bool, Kredensial?: array<string, mixed>}  $data
     */
    public function jalankan(
        string $organisasiId,
        KategoriPenyediaLayanan $kategori,
        string $kode,
        array $data,
        ?string $penggunaId = null,
    ): PenyediaLayananOrganisasi {
        $deskripsi = $kategori->bolehMilikOrganisasi() ? $this->katalog->untuk($kategori, $kode) : null;

        if ($deskripsi === null) {
            throw new DataTidakDitemukan("Penyedia {$kode} tidak dapat dipasang organisasi.");
        }

        return $this->transaksi->jalankan(function () use ($organisasiId, $kategori, $kode, $data, $penggunaId, $deskripsi): PenyediaLayananOrganisasi {
            $baris = PenyediaLayananOrganisasi::query()
                ->where('OrganisasiId', $organisasiId)
                ->where('Kategori', $kategori->value)
                ->where('Kode', $kode)
                ->lockForUpdate()
                ->first() ?? new PenyediaLayananOrganisasi([
                    'OrganisasiId' => $organisasiId,
                    'Kategori' => $kategori,
                    'Kode' => $kode,
                ]);

            $sidikLama = $baris->SidikKredensial;
            $nilai = $this->penyusun->gabungkan($deskripsi, $baris->nilaiKredensial(), (array) ($data['Kredensial'] ?? []), organisasi: true);
            $aktif = (bool) ($data['Aktif'] ?? $baris->Aktif);

            if ($aktif) {
                $this->penyusun->pastikanLengkap($deskripsi, $nilai, organisasi: true);
            }

            $sidikBaru = $nilai === [] ? null : $this->penyusun->sidik($nilai);
            $baris->fill([
                'Aktif' => $aktif,
                'ModeUji' => $deskripsi->mendukungModeUji() && (bool) ($data['ModeUji'] ?? $baris->ModeUji),
                'KredensialTerenkripsi' => $nilai === [] ? null : $nilai,
                'SidikKredensial' => $sidikBaru,
                'DiperbaruiOleh' => $penggunaId,
            ]);

            if ($sidikLama !== $sidikBaru) {
                $baris->fill(['TerakhirBerhasilPada' => null, 'TerakhirGagalPada' => null, 'GalatTerakhir' => null]);
            }

            $baris->save();

            if ($aktif) {
                PenyediaLayananOrganisasi::query()
                    ->where('OrganisasiId', $organisasiId)
                    ->where('Kategori', $kategori->value)
                    ->whereKeyNot($baris->Id)
                    ->update(['Aktif' => false]);
            }

            $this->audit->catat(
                'PenyediaLayananOrganisasi.Diubah',
                'PenyediaLayananOrganisasi',
                $baris->Id,
                dataSesudah: [
                    'Kategori' => $kategori->value,
                    'Kode' => $kode,
                    'Aktif' => $baris->Aktif,
                    'ModeUji' => $baris->ModeUji,
                    // Hanya penanda bahwa kredensial berganti; nilainya tidak pernah masuk audit.
                    'KredensialBerubah' => $sidikLama !== $sidikBaru,
                ],
            );

            return $baris->refresh();
        });
    }
}
