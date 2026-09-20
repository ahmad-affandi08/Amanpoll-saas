<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Notifikasi\Infrastructure\Persistence\Models\EskalasiTingkatLayanan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\AturanTingkatLayanan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\TingkatLayanan;
use App\Shared\Domain\Contracts\TransaksiDatabase;

final class SimpanTingkatLayanan
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananAudit $audit,
    ) {}

    /** @param array<string, mixed> $data */
    public function jalankan(array $data, ?TingkatLayanan $tingkatLayanan = null): TingkatLayanan
    {
        return $this->transaksi->jalankan(function () use ($data, $tingkatLayanan): TingkatLayanan {
            $tingkatLayanan ??= new TingkatLayanan;
            $sebelum = $tingkatLayanan->exists ? $tingkatLayanan->toArray() : null;
            $tingkatLayanan->fill(collect($data)->except(['Aturan', 'Eskalasi'])->all());
            $tingkatLayanan->save();

            AturanTingkatLayanan::query()->where('TingkatLayananId', $tingkatLayanan->Id)->delete();
            foreach ($data['Aturan'] as $aturan) {
                $tingkatLayanan->aturan()->create($aturan);
            }

            EskalasiTingkatLayanan::query()->where('TingkatLayananId', $tingkatLayanan->Id)->delete();
            foreach ($data['Eskalasi'] ?? [] as $eskalasi) {
                $tingkatLayanan->eskalasi()->create($eskalasi);
            }

            $tingkatLayanan->load(['aturan', 'eskalasi']);
            $this->audit->catat($sebelum ? 'Ubah' : 'Buat', 'TingkatLayanan', $tingkatLayanan->Id, $sebelum, $tingkatLayanan->toArray());

            return $tingkatLayanan;
        });
    }
}
