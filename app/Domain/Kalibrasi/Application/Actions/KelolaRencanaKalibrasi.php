<?php

declare(strict_types=1);

namespace App\Domain\Kalibrasi\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Core\Organisasi\KalenderOrganisasi;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\RencanaKalibrasi;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\Carbon;

final class KelolaRencanaKalibrasi
{
    public function __construct(
        private readonly KonteksOrganisasi $konteksOrganisasi,
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananAudit $layananAudit,
        private readonly KalenderOrganisasi $kalender,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function buat(array $data, string $penggunaId): RencanaKalibrasi
    {
        return $this->transaksi->jalankan(function () use ($data): RencanaKalibrasi {
            $organisasiId = $this->konteksOrganisasi->wajibId();

            $aset = Aset::query()
                ->where('OrganisasiId', $organisasiId)
                ->findOrFail($data['AsetId']);

            $intervalHari = (int) ($data['IntervalHari'] ?? 365);
            if ($intervalHari <= 0) {
                throw new AturanBisnisDilanggar('Interval kalibrasi harus lebih dari 0 hari.');
            }

            // Bawaannya tanggal hari ini di rumah sakit itu, bukan saat ini dalam UTC:
            // TanggalMulai kolom tanggal, dan tanggal UTC tertinggal sehari selama
            // jam-jam pertama tiap hari.
            $tanggalMulai = isset($data['TanggalMulai'])
                ? Carbon::parse($data['TanggalMulai'])
                : Carbon::instance($this->kalender->hariIni($organisasiId));
            $tanggalBerikutnya = isset($data['TanggalBerikutnya'])
                ? Carbon::parse($data['TanggalBerikutnya'])
                : (clone $tanggalMulai)->addDays($intervalHari);

            $rencana = RencanaKalibrasi::create([
                'OrganisasiId' => $organisasiId,
                'AsetId' => $aset->Id,
                'JenisKalibrasiId' => $data['JenisKalibrasiId'] ?? null,
                'PenyediaId' => $data['PenyediaId'] ?? null,
                'IntervalHari' => $intervalHari,
                'TanggalMulai' => $tanggalMulai->toDateString(),
                'TanggalBerikutnya' => $tanggalBerikutnya->toDateString(),
                'PeringatanHariSebelum' => (int) ($data['PeringatanHariSebelum'] ?? 30),
                'Aktif' => $data['Aktif'] ?? true,
            ]);

            $this->layananAudit->catat(
                aksi: 'RencanaKalibrasi.Dibuat',
                jenisEntitas: 'RencanaKalibrasi',
                entitasId: $rencana->Id,
                dataSesudah: $rencana->toArray(),
            );

            return $rencana;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function perbarui(RencanaKalibrasi $rencana, array $data, string $penggunaId): RencanaKalibrasi
    {
        return $this->transaksi->jalankan(function () use ($rencana, $data): RencanaKalibrasi {
            $organisasiId = $this->konteksOrganisasi->wajibId();

            if (isset($data['AsetId']) && $data['AsetId'] !== $rencana->AsetId) {
                Aset::query()->where('OrganisasiId', $organisasiId)->findOrFail($data['AsetId']);
            }

            $dataLama = $rencana->toArray();

            $intervalHari = isset($data['IntervalHari']) ? (int) $data['IntervalHari'] : $rencana->IntervalHari;
            if ($intervalHari <= 0) {
                throw new AturanBisnisDilanggar('Interval kalibrasi harus lebih dari 0 hari.');
            }

            $tanggalMulai = isset($data['TanggalMulai']) ? Carbon::parse($data['TanggalMulai']) : $rencana->TanggalMulai;
            $tanggalBerikutnya = isset($data['TanggalBerikutnya'])
                ? Carbon::parse($data['TanggalBerikutnya'])
                : $rencana->TanggalBerikutnya;

            $rencana->update([
                'AsetId' => $data['AsetId'] ?? $rencana->AsetId,
                'JenisKalibrasiId' => array_key_exists('JenisKalibrasiId', $data) ? $data['JenisKalibrasiId'] : $rencana->JenisKalibrasiId,
                'PenyediaId' => array_key_exists('PenyediaId', $data) ? $data['PenyediaId'] : $rencana->PenyediaId,
                'IntervalHari' => $intervalHari,
                'TanggalMulai' => $tanggalMulai?->toDateString(),
                'TanggalBerikutnya' => $tanggalBerikutnya?->toDateString(),
                'PeringatanHariSebelum' => isset($data['PeringatanHariSebelum']) ? (int) $data['PeringatanHariSebelum'] : $rencana->PeringatanHariSebelum,
                'Aktif' => $data['Aktif'] ?? $rencana->Aktif,
            ]);

            $this->layananAudit->catat(
                aksi: 'RencanaKalibrasi.Diperbarui',
                jenisEntitas: 'RencanaKalibrasi',
                entitasId: $rencana->Id,
                dataSebelum: $dataLama,
                dataSesudah: $rencana->fresh()->toArray(),
            );

            return $rencana->fresh();
        });
    }

    public function hapus(RencanaKalibrasi $rencana, string $penggunaId): void
    {
        $this->transaksi->jalankan(function () use ($rencana): void {
            if ($rencana->pelaksanaanKalibrasi()->exists()) {
                throw new AturanBisnisDilanggar('Rencana kalibrasi tidak dapat dihapus karena sudah memiliki riwayat pelaksanaan kalibrasi. Anda dapat menonaktifkannya.');
            }

            $dataLama = $rencana->toArray();
            $rencana->delete();

            $this->layananAudit->catat(
                aksi: 'RencanaKalibrasi.Dihapus',
                jenisEntitas: 'RencanaKalibrasi',
                entitasId: $rencana->Id,
                dataSebelum: $dataLama,
            );
        });
    }
}
