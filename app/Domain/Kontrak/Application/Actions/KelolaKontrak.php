<?php

declare(strict_types=1);

namespace App\Domain\Kontrak\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Kontrak\Domain\Enums\StatusKontrak;
use App\Domain\Kontrak\Infrastructure\Persistence\Models\Kontrak;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;

final class KelolaKontrak
{
    public function __construct(
        private readonly KonteksOrganisasi $konteks,
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananAudit $audit,
    ) {}

    /** @param array<string, mixed> $data */
    public function buat(array $data): Kontrak
    {
        $this->pastikanPeriodeValid($data['MulaiPada'], $data['BerakhirPada']);

        return $this->transaksi->jalankan(function () use ($data): Kontrak {
            $kontrak = Kontrak::create([
                'OrganisasiId' => $this->konteks->wajibId(),
                'PenyediaId' => $data['PenyediaId'] ?? null,
                'Nomor' => $data['Nomor'],
                'Nama' => $data['Nama'],
                'Jenis' => $data['Jenis'],
                'MulaiPada' => $data['MulaiPada'],
                'BerakhirPada' => $data['BerakhirPada'],
                'Nilai' => $data['Nilai'] ?? null,
                'MataUang' => $data['MataUang'] ?? 'IDR',
                'TingkatLayananId' => $data['TingkatLayananId'] ?? null,
                'PeringatanHariSebelum' => $data['PeringatanHariSebelum'] ?? 30,
                'Status' => StatusKontrak::Aktif->value,
                'Catatan' => $data['Catatan'] ?? null,
            ]);
            $this->audit->catat('Kontrak.Dibuat', 'Kontrak', $kontrak->Id, dataSesudah: $kontrak->toArray());

            return $kontrak;
        });
    }

    /** @param array<string, mixed> $data */
    public function ubah(Kontrak $kontrak, array $data): Kontrak
    {
        if ($kontrak->Status === StatusKontrak::Dibatalkan->value) {
            throw new AturanBisnisDilanggar('Kontrak yang dibatalkan tidak dapat diubah.');
        }
        $this->pastikanPeriodeValid($data['MulaiPada'], $data['BerakhirPada']);

        return $this->transaksi->jalankan(function () use ($kontrak, $data): Kontrak {
            $sebelum = $kontrak->toArray();
            $kontrak->fill([
                'PenyediaId' => $data['PenyediaId'] ?? null,
                'Nomor' => $data['Nomor'],
                'Nama' => $data['Nama'],
                'Jenis' => $data['Jenis'],
                'MulaiPada' => $data['MulaiPada'],
                'BerakhirPada' => $data['BerakhirPada'],
                'Nilai' => $data['Nilai'] ?? null,
                'MataUang' => $data['MataUang'] ?? 'IDR',
                'TingkatLayananId' => $data['TingkatLayananId'] ?? null,
                'PeringatanHariSebelum' => $data['PeringatanHariSebelum'] ?? 30,
                'Catatan' => $data['Catatan'] ?? null,
            ]);
            $this->pastikanCakupanAsetMasukPeriode($kontrak);
            $kontrak->save();
            $this->audit->catat('Kontrak.Diubah', 'Kontrak', $kontrak->Id, dataSebelum: $sebelum, dataSesudah: $kontrak->toArray());

            return $kontrak->refresh();
        });
    }

    /** Membatalkan kontrak; riwayat cakupan aset sengaja dipertahankan. */
    public function batalkan(Kontrak $kontrak, string $alasan): Kontrak
    {
        if ($kontrak->Status !== StatusKontrak::Aktif->value) {
            throw new AturanBisnisDilanggar('Hanya kontrak aktif yang dapat dibatalkan.');
        }

        $kontrak->Status = StatusKontrak::Dibatalkan->value;
        $kontrak->Catatan = trim(($kontrak->Catatan ?? '')."\nDibatalkan: ".$alasan);
        $kontrak->save();
        $this->audit->catat('Kontrak.Dibatalkan', 'Kontrak', $kontrak->Id, dataSesudah: ['Status' => $kontrak->Status, 'Alasan' => $alasan]);

        return $kontrak->refresh();
    }

    public function hapus(Kontrak $kontrak): void
    {
        if ($kontrak->kontrakAset()->exists() || $kontrak->layanan()->exists()) {
            throw new AturanBisnisDilanggar('Kontrak yang memiliki cakupan aset atau layanan tidak dapat dihapus.');
        }

        $this->transaksi->jalankan(function () use ($kontrak): void {
            $id = $kontrak->Id;
            $sebelum = $kontrak->toArray();
            $kontrak->delete();
            $this->audit->catat('Kontrak.Dihapus', 'Kontrak', $id, dataSebelum: $sebelum);
        });
    }

    private function pastikanPeriodeValid(mixed $mulai, mixed $berakhir): void
    {
        if (CarbonImmutable::parse((string) $berakhir)->lt(CarbonImmutable::parse((string) $mulai))) {
            throw new AturanBisnisDilanggar('Tanggal berakhir kontrak tidak boleh mendahului tanggal mulai.');
        }
    }

    /** Periode cakupan aset tidak boleh keluar dari periode kontrak setelah kontrak diubah. */
    private function pastikanCakupanAsetMasukPeriode(Kontrak $kontrak): void
    {
        $mulai = CarbonImmutable::parse((string) $kontrak->MulaiPada);
        $berakhir = CarbonImmutable::parse((string) $kontrak->BerakhirPada);

        foreach ($kontrak->kontrakAset()->get() as $cakupan) {
            $mulaiCakupan = $cakupan->MulaiPada ? CarbonImmutable::parse((string) $cakupan->MulaiPada) : $mulai;
            $berakhirCakupan = $cakupan->BerakhirPada ? CarbonImmutable::parse((string) $cakupan->BerakhirPada) : $berakhir;

            if ($mulaiCakupan->lt($mulai) || $berakhirCakupan->gt($berakhir)) {
                throw new AturanBisnisDilanggar('Periode kontrak baru tidak mencakup seluruh periode aset yang sudah terdaftar.');
            }
        }
    }
}
