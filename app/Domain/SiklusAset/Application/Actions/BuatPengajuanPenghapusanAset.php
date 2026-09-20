<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Application\Services\LayananNomorDokumen;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\PengajuanPenghapusanAset;

final class BuatPengajuanPenghapusanAset
{
    private const JENIS_DOKUMEN = 'PengajuanPenghapusanAset';

    public function __construct(
        private readonly LayananNomorDokumen $layananNomorDokumen,
        private readonly KonteksOrganisasi $konteksOrganisasi,
        private readonly LayananAudit $layananAudit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function jalankan(array $data, string $diajukanOleh): PengajuanPenghapusanAset
    {
        $data['Nomor'] = $this->layananNomorDokumen->berikutnya($this->konteksOrganisasi->wajibId(), self::JENIS_DOKUMEN);
        $data['DiajukanOleh'] = $diajukanOleh;
        $data['DiajukanPada'] = now();
        $data['Status'] = PengajuanPenghapusanAset::STATUS_DRAFT;

        /** @var PengajuanPenghapusanAset $pengajuan */
        $pengajuan = PengajuanPenghapusanAset::create($data);

        $this->layananAudit->catat(
            aksi: 'PengajuanPenghapusanAset.Dibuat',
            jenisEntitas: 'PengajuanPenghapusanAset',
            entitasId: $pengajuan->Id,
            dataSesudah: ['Nomor' => $pengajuan->Nomor, 'Alasan' => $pengajuan->Alasan],
        );

        return $pengajuan;
    }
}
