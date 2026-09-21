<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\SertifikasiAset;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;

final class KelolaSertifikasiAset
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananAudit $audit,
    ) {}

    /** @param array<string, mixed> $data */
    public function terbitkan(Aset $aset, array $data): SertifikasiAset
    {
        $this->pastikanPeriodeValid($data);

        return $this->transaksi->jalankan(function () use ($aset, $data): SertifikasiAset {
            $sertifikasi = SertifikasiAset::create([
                'OrganisasiId' => $aset->OrganisasiId,
                'AsetId' => $aset->Id,
                'JenisSertifikasi' => $data['JenisSertifikasi'],
                'NomorSertifikat' => $data['NomorSertifikat'] ?? null,
                'Penerbit' => $data['Penerbit'] ?? null,
                'TerbitPada' => $data['TerbitPada'] ?? null,
                'BerlakuSampai' => $data['BerlakuSampai'] ?? null,
                'Status' => SertifikasiAset::STATUS_AKTIF,
                'BerkasId' => $data['BerkasId'] ?? null,
            ]);
            $this->audit->catat('SertifikasiAset.Diterbitkan', 'Aset', $aset->Id, dataSesudah: $sertifikasi->toArray());

            return $sertifikasi;
        });
    }

    /** @param array<string, mixed> $data */
    public function ubah(SertifikasiAset $sertifikasi, array $data): SertifikasiAset
    {
        if ($sertifikasi->Status === SertifikasiAset::STATUS_DICABUT) {
            throw new AturanBisnisDilanggar('Sertifikat yang sudah dicabut tidak dapat diubah.');
        }
        $this->pastikanPeriodeValid($data);

        $sebelum = $sertifikasi->toArray();
        $sertifikasi->fill([
            'JenisSertifikasi' => $data['JenisSertifikasi'],
            'NomorSertifikat' => $data['NomorSertifikat'] ?? null,
            'Penerbit' => $data['Penerbit'] ?? null,
            'TerbitPada' => $data['TerbitPada'] ?? null,
            'BerlakuSampai' => $data['BerlakuSampai'] ?? null,
            'BerkasId' => $data['BerkasId'] ?? null,
        ]);

        // Perpanjangan masa berlaku mengembalikan sertifikat kedaluwarsa menjadi aktif.
        if ($sertifikasi->Status === SertifikasiAset::STATUS_KEDALUWARSA
            && $sertifikasi->BerlakuSampai !== null
            && CarbonImmutable::parse((string) $sertifikasi->BerlakuSampai)->gte(CarbonImmutable::today())) {
            $sertifikasi->Status = SertifikasiAset::STATUS_AKTIF;
        }

        $sertifikasi->save();
        $this->audit->catat('SertifikasiAset.Diubah', 'SertifikasiAset', $sertifikasi->Id, dataSebelum: $sebelum, dataSesudah: $sertifikasi->toArray());

        return $sertifikasi->refresh();
    }

    public function cabut(SertifikasiAset $sertifikasi, string $alasan): SertifikasiAset
    {
        if ($sertifikasi->Status === SertifikasiAset::STATUS_DICABUT) {
            throw new AturanBisnisDilanggar('Sertifikat sudah dicabut sebelumnya.');
        }

        $sertifikasi->Status = SertifikasiAset::STATUS_DICABUT;
        $sertifikasi->save();
        $this->audit->catat('SertifikasiAset.Dicabut', 'SertifikasiAset', $sertifikasi->Id, dataSesudah: [
            'Status' => $sertifikasi->Status,
            'Alasan' => $alasan,
        ]);

        return $sertifikasi->refresh();
    }

    /** @param array<string, mixed> $data */
    private function pastikanPeriodeValid(array $data): void
    {
        $terbit = $data['TerbitPada'] ?? null;
        $berlaku = $data['BerlakuSampai'] ?? null;

        if ($terbit !== null && $berlaku !== null
            && CarbonImmutable::parse((string) $berlaku)->lt(CarbonImmutable::parse((string) $terbit))) {
            throw new AturanBisnisDilanggar('Masa berlaku sertifikat tidak boleh mendahului tanggal terbit.');
        }
    }
}
