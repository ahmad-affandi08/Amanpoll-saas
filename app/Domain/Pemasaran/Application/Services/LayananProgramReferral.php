<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Core\Audit\LayananAudit;
use App\Domain\Langganan\Domain\Contracts\PemberiImbalanLangganan;
use App\Domain\Pemasaran\Domain\Enums\JenisRewardReferral;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\ProgramReferral;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/** Menyusun program referral dari konsol; imbalan yang belum didukung Billing ditolak di sini (MARKETING.md 20). */
final class LayananProgramReferral
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly PemberiImbalanLangganan $pemberi,
        private readonly LayananAudit $audit,
    ) {}

    /** @param array{Kode: string, Nama: string, Keterangan?: string|null, JenisReward: string, NilaiReward: float, HariKedaluwarsa: int, Aktif: bool} $data */
    public function simpan(?ProgramReferral $program, array $data): ProgramReferral
    {
        $jenis = JenisRewardReferral::tryFrom($data['JenisReward']);

        if ($jenis === null) {
            throw new AturanBisnisDilanggar("Jenis imbalan {$data['JenisReward']} tidak dikenal.");
        }

        // Janji imbalan yang tidak dapat ditepati terbaca pelanggan hari ini, gagalnya baru terlihat berminggu kemudian.
        if (! $jenis->manual() && ! $this->pemberi->mendukung($jenis->value)) {
            throw new AturanBisnisDilanggar(
                "Billing belum dapat memberikan imbalan berbentuk {$jenis->value}; pilih bentuk lain."
            );
        }

        return $this->transaksi->jalankan(function () use ($program, $data): ProgramReferral {
            $sebelum = $program?->only(['Kode', 'JenisReward', 'NilaiReward', 'Aktif']);

            if ($program === null) {
                $program = ProgramReferral::create($data);
            } else {
                $program->fill($data);
                $program->save();
            }

            $this->audit->catat(
                $sebelum === null ? 'ProgramReferral.Dibuat' : 'ProgramReferral.Diubah',
                'ProgramReferral',
                $program->Id,
                dataSebelum: $sebelum,
                dataSesudah: $program->only(['Kode', 'JenisReward', 'NilaiReward', 'Aktif']),
            );

            return $program;
        });
    }

    /** @return list<string> */
    public function jenisDidukung(): array
    {
        return array_values(array_filter(
            array_column(JenisRewardReferral::cases(), 'value'),
            fn (string $jenis): bool => JenisRewardReferral::from($jenis)->manual()
                || $this->pemberi->mendukung($jenis),
        ));
    }
}
