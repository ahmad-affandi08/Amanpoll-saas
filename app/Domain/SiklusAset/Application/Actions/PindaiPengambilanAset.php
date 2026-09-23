<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Aset\Application\Services\PencariAsetLewatKode;
use App\Domain\SiklusAset\Domain\Enums\StatusDetailMutasiAset;
use App\Domain\SiklusAset\Domain\Enums\StatusPermintaanMutasiAset;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\DetailMutasiAset;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\PermintaanMutasiAset;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/**
 * Verifikasi fisik saat aset diambil: kode di stiker aset dipindai, lalu
 * dicocokkan dengan daftar permintaan.
 *
 * Yang dijaga di sini bukan kelengkapan, melainkan kebenaran barangnya. Selama
 * daftar mutasi hanya berupa nama di layar, petugas gudang tidak punya cara
 * membedakan dua alat sejenis; pemindaian menolak kode yang bukan bagian dari
 * permintaan ini sebelum barangnya keluar ruangan.
 */
final class PindaiPengambilanAset
{
    /** Pemindaian dilakukan saat pengambilan, jadi permintaannya harus sudah diotorisasi. */
    private const STATUS_PERMINTAAN_SIAP_AMBIL = [
        StatusPermintaanMutasiAset::Disetujui->value,
    ];

    public function __construct(
        private readonly PencariAsetLewatKode $pencari,
        private readonly LayananAudit $layananAudit,
    ) {}

    public function jalankan(PermintaanMutasiAset $permintaan, string $kode, string $dipindaiOleh): DetailMutasiAset
    {
        if (! in_array($permintaan->Status, self::STATUS_PERMINTAAN_SIAP_AMBIL, true)) {
            throw new AturanBisnisDilanggar('Pemindaian pengambilan hanya berlaku pada permintaan yang sudah disetujui.');
        }

        $aset = $this->pencari->cari($kode);

        if ($aset === null) {
            throw new AturanBisnisDilanggar(sprintf('Kode "%s" tidak dikenali sebagai aset mana pun.', trim($kode)));
        }

        /** @var DetailMutasiAset|null $detail */
        $detail = $permintaan->detailMutasiAset()->where('AsetId', $aset->Id)->first();

        if ($detail === null) {
            throw new AturanBisnisDilanggar(sprintf(
                'Aset %s (%s) tidak termasuk dalam permintaan mutasi ini.',
                $aset->Nama,
                $aset->KodeAset,
            ));
        }

        if ($detail->Status === StatusDetailMutasiAset::Ditolak->value) {
            throw new AturanBisnisDilanggar(sprintf('Aset %s ditolak untuk mutasi ini dan tidak boleh diambil.', $aset->KodeAset));
        }

        if ($detail->Status === StatusDetailMutasiAset::Dibatalkan->value) {
            throw new AturanBisnisDilanggar(sprintf('Aset %s sudah dikeluarkan dari permintaan ini.', $aset->KodeAset));
        }

        if ($detail->Status === StatusDetailMutasiAset::Selesai->value) {
            throw new AturanBisnisDilanggar(sprintf('Aset %s sudah dipindahkan sebelumnya.', $aset->KodeAset));
        }

        // Pemindaian ulang tidak menimpa jejak yang pertama; yang bernilai untuk
        // penelusuran adalah siapa yang benar-benar memegang barangnya saat diambil.
        if ($detail->DipindaiPada !== null) {
            return $detail;
        }

        $detail->DipindaiOleh = $dipindaiOleh;
        $detail->DipindaiPada = now()->toImmutable();
        $detail->save();

        $this->layananAudit->catat(
            aksi: 'DetailMutasiAset.Dipindai',
            jenisEntitas: 'DetailMutasiAset',
            entitasId: $detail->Id,
            dataSesudah: ['AsetId' => $aset->Id, 'KodeAset' => $aset->KodeAset],
        );

        return $detail;
    }
}
