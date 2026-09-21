<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\IntegrasiEksternal;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\PemetaanDataEksternal;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/**
 * Pemetaan identitas internal ke identitas sistem eksternal (19.02).
 * Satu kode eksternal hanya boleh menunjuk satu entitas internal; bentrokan
 * ditandai sebagai konflik dan menunggu penyelesaian manual.
 */
final class KelolaPemetaanDataEksternal
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananAudit $audit,
    ) {}

    /** @param array<string, mixed> $data */
    public function petakan(IntegrasiEksternal $integrasi, array $data): PemetaanDataEksternal
    {
        return $this->transaksi->jalankan(function () use ($integrasi, $data): PemetaanDataEksternal {
            $bentrok = PemetaanDataEksternal::query()
                ->where('IntegrasiEksternalId', $integrasi->Id)
                ->where('KodeEksternal', $data['KodeEksternal'])
                ->where('JenisEntitas', $data['JenisEntitas'])
                ->where('EntitasId', '!=', $data['EntitasId'])
                ->first();

            $pemetaan = PemetaanDataEksternal::query()
                ->where('IntegrasiEksternalId', $integrasi->Id)
                ->where('JenisEntitas', $data['JenisEntitas'])
                ->where('EntitasId', $data['EntitasId'])
                ->first();

            $tambahan = $data['DataTambahan'] ?? [];
            if ($bentrok instanceof PemetaanDataEksternal) {
                $tambahan['Konflik'] = true;
                $tambahan['KonflikDenganId'] = $bentrok->Id;
                $tambahan['AlasanKonflik'] = "Kode eksternal {$data['KodeEksternal']} sudah dipakai entitas lain.";
            }

            if ($pemetaan instanceof PemetaanDataEksternal) {
                $pemetaan->KodeEksternal = $data['KodeEksternal'];
                $pemetaan->DataTambahan = $tambahan === [] ? null : $tambahan;
                $pemetaan->save();
            } else {
                $pemetaan = PemetaanDataEksternal::create([
                    'OrganisasiId' => $integrasi->OrganisasiId,
                    'IntegrasiEksternalId' => $integrasi->Id,
                    'JenisEntitas' => $data['JenisEntitas'],
                    'EntitasId' => $data['EntitasId'],
                    'KodeEksternal' => $data['KodeEksternal'],
                    'DataTambahan' => $tambahan === [] ? null : $tambahan,
                ]);
            }

            $this->audit->catat('PemetaanDataEksternal.Disimpan', 'IntegrasiEksternal', $integrasi->Id, dataSesudah: [
                'JenisEntitas' => $pemetaan->JenisEntitas,
                'EntitasId' => $pemetaan->EntitasId,
                'KodeEksternal' => $pemetaan->KodeEksternal,
                'Konflik' => $this->berkonflik($pemetaan),
            ]);

            return $pemetaan->refresh();
        });
    }

    public function berkonflik(PemetaanDataEksternal $pemetaan): bool
    {
        return (bool) ($pemetaan->DataTambahan['Konflik'] ?? false);
    }

    /**
     * Menyelesaikan konflik secara manual: pemetaan yang kalah dilepas dan
     * penanda konflik dibersihkan.
     */
    public function selesaikanKonflik(PemetaanDataEksternal $pemetaan, bool $pertahankan): void
    {
        if (! $this->berkonflik($pemetaan)) {
            throw new AturanBisnisDilanggar('Pemetaan ini tidak sedang berkonflik.');
        }

        $this->transaksi->jalankan(function () use ($pemetaan, $pertahankan): void {
            $tambahan = $pemetaan->DataTambahan ?? [];
            $lawanId = $tambahan['KonflikDenganId'] ?? null;

            if ($pertahankan) {
                if ($lawanId !== null) {
                    PemetaanDataEksternal::query()->whereKey($lawanId)->delete();
                }
                unset($tambahan['Konflik'], $tambahan['KonflikDenganId'], $tambahan['AlasanKonflik']);
                $pemetaan->DataTambahan = $tambahan === [] ? null : $tambahan;
                $pemetaan->save();
            } else {
                $pemetaan->delete();
            }

            $this->audit->catat('PemetaanDataEksternal.KonflikDiselesaikan', 'PemetaanDataEksternal', $pemetaan->Id, dataSesudah: [
                'Dipertahankan' => $pertahankan,
            ]);
        });
    }

    public function lepas(PemetaanDataEksternal $pemetaan): void
    {
        $id = $pemetaan->Id;
        $pemetaan->delete();
        $this->audit->catat('PemetaanDataEksternal.Dilepas', 'PemetaanDataEksternal', $id);
    }
}
