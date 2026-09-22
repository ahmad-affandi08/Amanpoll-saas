<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Pemasaran\Application\Services\LayananKonsen;
use App\Domain\Pemasaran\Domain\Enums\AlasanSupresi;
use App\Domain\Pemasaran\Domain\Enums\JenisPermintaanData;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\PermintaanDataProspek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Prospek;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;

/** Permintaan penghapusan data; alamatnya disupresi saat diterima, bukan saat diproses (MARKETING.md 27). */
final class CatatPermintaanData
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananKonsen $konsen,
        private readonly LayananAudit $audit,
    ) {}

    public function jalankan(
        string $email,
        JenisPermintaanData $jenis,
        ?Prospek $prospek = null,
        ?string $catatan = null,
    ): PermintaanDataProspek {
        $bersih = mb_strtolower(trim($email));

        if ($bersih === '') {
            throw new AturanBisnisDilanggar('Permintaan data memerlukan alamat email.');
        }

        return $this->transaksi->jalankan(function () use ($bersih, $jenis, $prospek, $catatan): PermintaanDataProspek {
            $this->konsen->supresi($bersih, AlasanSupresi::Penghapusan, $catatan);

            $permintaan = PermintaanDataProspek::create([
                'ProspekId' => $prospek?->Id,
                'EmailHash' => $this->konsen->sidik($bersih),
                'Email' => $bersih,
                'Jenis' => $jenis,
                'Catatan' => $catatan,
                'DimintaPada' => CarbonImmutable::now(),
            ]);

            $this->audit->catat(
                'PermintaanDataProspek.Diterima',
                'PermintaanDataProspek',
                $permintaan->Id,
                dataSesudah: ['Email' => $bersih, 'Jenis' => $jenis->value],
            );

            return $permintaan;
        });
    }
}
