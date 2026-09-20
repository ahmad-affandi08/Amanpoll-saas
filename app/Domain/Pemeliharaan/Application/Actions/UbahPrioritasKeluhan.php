<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Pemeliharaan\Application\Services\LayananKalkulasiSla;
use App\Domain\Pemeliharaan\Domain\Enums\PrioritasKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\AturanTingkatLayanan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\VersiDataBerubah;

final class UbahPrioritasKeluhan
{
    public function __construct(
        private readonly KonteksOrganisasi $konteks,
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananKalkulasiSla $kalkulasiSla,
        private readonly LayananAudit $audit,
    ) {}

    public function jalankan(Keluhan $keluhan, PrioritasKeluhan $prioritas, string $alasan, int $versi): Keluhan
    {
        return $this->transaksi->jalankan(function () use ($keluhan, $prioritas, $alasan, $versi): Keluhan {
            /** @var Keluhan $terkunci */
            $terkunci = Keluhan::query()->with(['tingkatLayanan', 'lokasi', 'organisasi'])->lockForUpdate()->findOrFail($keluhan->Id);
            if ($terkunci->Versi !== $versi) {
                throw new VersiDataBerubah('Keluhan telah berubah. Muat ulang halaman sebelum mencoba lagi.');
            }

            $sebelum = $terkunci->toArray();
            $terkunci->Prioritas = $prioritas->value;
            $terkunci->Versi++;
            $terkunci->BatasResponsPada = null;
            $terkunci->BatasPenyelesaianPada = null;

            if ($terkunci->tingkatLayanan !== null) {
                $aturan = AturanTingkatLayanan::query()
                    ->where('TingkatLayananId', $terkunci->TingkatLayananId)
                    ->where('Prioritas', $prioritas->value)
                    ->first();
                if ($aturan !== null) {
                    $batas = $this->kalkulasiSla->hitungBatas(
                        $this->konteks->wajibId(),
                        $terkunci->tingkatLayanan,
                        $aturan,
                        $terkunci->DilaporkanPada,
                        $terkunci->lokasi?->ZonaWaktu ?: $terkunci->organisasi->ZonaWaktu,
                        $terkunci->LokasiId,
                    );
                    $terkunci->BatasResponsPada = $batas['respons'];
                    $terkunci->BatasPenyelesaianPada = $batas['penyelesaian'];
                }
            }

            $terkunci->save();
            $sesudah = [...$terkunci->toArray(), 'AlasanPerubahanPrioritas' => $alasan];
            $this->audit->catat('UbahPrioritas', 'Keluhan', $terkunci->Id, $sebelum, $sesudah);

            return $terkunci;
        });
    }
}
