<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Application\Services\LayananNomorDokumen;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\SerahTerimaAset;

final class BuatSerahTerimaAset
{
    private const JENIS_DOKUMEN = 'SerahTerimaAset';

    public function __construct(
        private readonly LayananNomorDokumen $layananNomorDokumen,
        private readonly KonteksOrganisasi $konteksOrganisasi,
        private readonly LayananAudit $layananAudit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function jalankan(array $data): SerahTerimaAset
    {
        $data['Nomor'] = $this->layananNomorDokumen->berikutnya($this->konteksOrganisasi->wajibId(), self::JENIS_DOKUMEN);
        $data['Status'] = SerahTerimaAset::STATUS_DISERAHKAN;
        $data['DiserahkanPada'] = now();

        /** @var SerahTerimaAset $serahTerima */
        $serahTerima = SerahTerimaAset::create($data);

        $this->layananAudit->catat(
            aksi: 'SerahTerimaAset.Dibuat',
            jenisEntitas: 'SerahTerimaAset',
            entitasId: $serahTerima->Id,
            dataSesudah: ['Nomor' => $serahTerima->Nomor, 'Jenis' => $serahTerima->Jenis],
        );

        return $serahTerima;
    }
}
