<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Actions;

use App\Domain\Pemasaran\Domain\Enums\JenisAktivitasProspek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\AktivitasProspek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Prospek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\RiwayatTahapProspek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\TahapPipeline;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;

/**
 * Perpindahan tahap prospek (MARKETING.md 5.3).
 *
 * Setiap perpindahan menulis riwayat dan satu entri timeline. Keduanya wajib,
 * dan keduanya ditulis di sini supaya tidak ada jalur yang memindahkan tahap
 * tanpa meninggalkan jejak — corong yang kehilangan satu perpindahan akan
 * salah menghitung lama tahapnya untuk selamanya.
 */
final class PindahkanTahapProspek
{
    public function __construct(private readonly TransaksiDatabase $transaksi) {}

    public function jalankan(Prospek $prospek, TahapPipeline $tujuan, ?string $alasan = null): Prospek
    {
        return $this->transaksi->jalankan(function () use ($prospek, $tujuan, $alasan): Prospek {
            $asalId = $prospek->TahapPipelineId;

            if ($asalId === $tujuan->Id) {
                throw new AturanBisnisDilanggar('Prospek sudah berada pada tahap tersebut.');
            }

            $asal = $asalId === null ? null : TahapPipeline::query()->find($asalId);

            $prospek->TahapPipelineId = $tujuan->Id;
            $prospek->AktivitasTerakhirPada = CarbonImmutable::now();
            $prospek->save();

            RiwayatTahapProspek::create([
                'ProspekId' => $prospek->Id,
                'TahapSebelumId' => $asalId,
                'TahapSesudahId' => $tujuan->Id,
                'AktorPlatformId' => Auth::guard('platform')->id(),
                'Alasan' => $alasan,
                'BerpindahPada' => now(),
            ]);

            AktivitasProspek::create([
                'ProspekId' => $prospek->Id,
                'AktorPlatformId' => Auth::guard('platform')->id(),
                'Jenis' => JenisAktivitasProspek::PerubahanTahap->value,
                'Judul' => $asal === null
                    ? "Masuk tahap {$tujuan->Nama}"
                    : "Pindah dari {$asal->Nama} ke {$tujuan->Nama}",
                'Isi' => $alasan,
                'TerjadiPada' => now(),
            ]);

            return $prospek;
        });
    }

    /**
     * Tahap pertama prospek baru. Dipisah dari `jalankan()` karena tidak ada
     * tahap asal yang berpindah — yang dicatat adalah titik masuknya.
     */
    public function catatTahapAwal(Prospek $prospek): void
    {
        if ($prospek->TahapPipelineId === null) {
            return;
        }

        RiwayatTahapProspek::create([
            'ProspekId' => $prospek->Id,
            'TahapSebelumId' => null,
            'TahapSesudahId' => $prospek->TahapPipelineId,
            'AktorPlatformId' => Auth::guard('platform')->id(),
            'BerpindahPada' => now(),
        ]);
    }
}
