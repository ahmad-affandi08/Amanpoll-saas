<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Notifikasi\Infrastructure\Persistence\Models\EskalasiTingkatLayanan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\AturanTingkatLayanan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\TingkatLayanan;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

final class HapusTingkatLayanan
{
    public function __construct(private readonly TransaksiDatabase $transaksi, private readonly LayananAudit $audit) {}

    public function jalankan(TingkatLayanan $tingkatLayanan): void
    {
        if ($tingkatLayanan->kategoriKeluhan()->exists() || Keluhan::query()->where('TingkatLayananId', $tingkatLayanan->Id)->exists()) {
            throw new AturanBisnisDilanggar('Tingkat layanan masih dipakai kategori atau keluhan. Nonaktifkan sebagai pengganti penghapusan.');
        }

        $this->transaksi->jalankan(function () use ($tingkatLayanan): void {
            $sebelum = $tingkatLayanan->toArray();
            AturanTingkatLayanan::query()->where('TingkatLayananId', $tingkatLayanan->Id)->delete();
            EskalasiTingkatLayanan::query()->where('TingkatLayananId', $tingkatLayanan->Id)->delete();
            $tingkatLayanan->delete();
            $this->audit->catat('Hapus', 'TingkatLayanan', $tingkatLayanan->Id, $sebelum);
        });
    }
}
