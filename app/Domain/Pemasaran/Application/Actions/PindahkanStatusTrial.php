<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Pemasaran\Domain\Enums\StatusTrial;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Trial;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;

/** Satu-satunya jalur yang mengubah status trial (MARKETING.md 12). */
final class PindahkanStatusTrial
{
    public function __construct(private readonly LayananAudit $audit) {}

    public function jalankan(Trial $trial, StatusTrial $tujuan, ?string $alasan = null): Trial
    {
        $sebelum = $trial->Status;

        if (! $sebelum->bolehPindahKe($tujuan)) {
            throw new AturanBisnisDilanggar(
                "Trial tidak dapat berpindah dari {$sebelum->value} ke {$tujuan->value}.",
            );
        }

        $trial->Status = $tujuan;

        if ($tujuan === StatusTrial::Teraktivasi && $trial->TeraktivasiPada === null) {
            $trial->TeraktivasiPada = CarbonImmutable::now();
        }

        if ($tujuan === StatusTrial::Konversi && $trial->KonversiPada === null) {
            $trial->KonversiPada = CarbonImmutable::now();
        }

        $trial->save();

        $this->audit->catat(
            'Trial.StatusBerubah',
            'Trial',
            $trial->Id,
            dataSebelum: ['Status' => $sebelum->value],
            dataSesudah: ['Status' => $tujuan->value, 'Alasan' => $alasan],
        );

        return $trial;
    }
}
