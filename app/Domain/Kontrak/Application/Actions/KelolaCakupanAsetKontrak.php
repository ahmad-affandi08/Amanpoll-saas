<?php

declare(strict_types=1);

namespace App\Domain\Kontrak\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Kontrak\Domain\Enums\StatusKontrak;
use App\Domain\Kontrak\Infrastructure\Persistence\Models\Kontrak;
use App\Domain\Kontrak\Infrastructure\Persistence\Models\KontrakAset;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;

final class KelolaCakupanAsetKontrak
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananAudit $audit,
    ) {}

    /** @param array<string, mixed> $data */
    public function lampirkan(Kontrak $kontrak, array $data): KontrakAset
    {
        if ($kontrak->Status !== StatusKontrak::Aktif->value) {
            throw new AturanBisnisDilanggar('Aset hanya dapat dilampirkan pada kontrak berstatus aktif.');
        }

        // Scope organisasi pada model menjaga aset lintas tenant tidak pernah ditemukan.
        $aset = Aset::query()->whereKey($data['AsetId'])->first();
        if (! $aset instanceof Aset) {
            throw new AturanBisnisDilanggar('Aset tidak ditemukan pada organisasi ini.');
        }

        if ($kontrak->kontrakAset()->where('AsetId', $aset->Id)->exists()) {
            throw new AturanBisnisDilanggar("Aset {$aset->KodeAset} sudah tercakup kontrak ini.");
        }

        $mulai = $data['MulaiPada'] ?? $kontrak->MulaiPada->toDateString();
        $berakhir = $data['BerakhirPada'] ?? $kontrak->BerakhirPada->toDateString();
        $this->pastikanPeriodeDalamKontrak($kontrak, (string) $mulai, (string) $berakhir);

        return $this->transaksi->jalankan(function () use ($kontrak, $aset, $mulai, $berakhir, $data): KontrakAset {
            $cakupan = KontrakAset::create([
                'OrganisasiId' => $kontrak->OrganisasiId,
                'KontrakId' => $kontrak->Id,
                'AsetId' => $aset->Id,
                'MulaiPada' => $mulai,
                'BerakhirPada' => $berakhir,
                'Catatan' => $data['Catatan'] ?? null,
            ]);
            $this->audit->catat('KontrakAset.Dilampirkan', 'Kontrak', $kontrak->Id, dataSesudah: $cakupan->toArray());

            return $cakupan;
        });
    }

    public function lepaskan(Kontrak $kontrak, KontrakAset $cakupan): void
    {
        if ($cakupan->KontrakId !== $kontrak->Id) {
            throw new AturanBisnisDilanggar('Cakupan aset bukan bagian dari kontrak ini.');
        }

        $this->transaksi->jalankan(function () use ($kontrak, $cakupan): void {
            $sebelum = $cakupan->toArray();
            $cakupan->delete();
            $this->audit->catat('KontrakAset.Dilepaskan', 'Kontrak', $kontrak->Id, dataSebelum: $sebelum);
        });
    }

    private function pastikanPeriodeDalamKontrak(Kontrak $kontrak, string $mulai, string $berakhir): void
    {
        $mulaiCakupan = CarbonImmutable::parse($mulai);
        $berakhirCakupan = CarbonImmutable::parse($berakhir);

        if ($berakhirCakupan->lt($mulaiCakupan)) {
            throw new AturanBisnisDilanggar('Periode cakupan aset tidak valid.');
        }

        if ($mulaiCakupan->lt(CarbonImmutable::parse((string) $kontrak->MulaiPada))
            || $berakhirCakupan->gt(CarbonImmutable::parse((string) $kontrak->BerakhirPada))) {
            throw new AturanBisnisDilanggar('Periode cakupan aset harus berada di dalam periode kontrak.');
        }
    }
}
