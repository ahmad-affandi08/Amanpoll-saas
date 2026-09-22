<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Jobs;

use App\Domain\Pemasaran\Application\Services\PengirimEmailPemasaran;
use App\Domain\Pemasaran\Domain\Contracts\PenyediaEmailPemasaran;
use App\Domain\Pemasaran\Domain\Enums\StatusPengirimanEmail;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\PengirimanEmailPemasaran;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Menarik status yang datang belakangan; status hanya boleh maju sehingga laporan tak berurutan tidak memundurkan (MARKETING.md 15). */
final class SinkronkanStatusProvider implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Status hanya boleh maju, jadi menarik ulang laporan yang sama tidak mengubah apa pun. */
    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [30, 120];

    private const BATAS = 200;

    public function handle(PenyediaEmailPemasaran $penyedia, PengirimEmailPemasaran $pengirim): void
    {
        $menunggu = PengirimanEmailPemasaran::query()
            ->whereNotNull('IdPesanPenyedia')
            ->whereIn('Status', $this->statusBelumFinal())
            ->orderBy('DikirimPada')
            ->limit(self::BATAS)
            ->get();

        if ($menunggu->isEmpty()) {
            return;
        }

        /** @var array<string, PengirimanEmailPemasaran> $peta */
        $peta = $menunggu->keyBy('IdPesanPenyedia')->all();
        $idPesan = array_map(strval(...), array_keys($peta));

        foreach ($penyedia->statusKiriman($idPesan) as $laporan) {
            $pengiriman = $peta[$laporan->idPesan] ?? null;

            if ($pengiriman !== null) {
                $pengirim->perbaruiStatus($pengiriman, $laporan->status, $laporan->keterangan);
            }
        }
    }

    /** @return list<string> */
    private function statusBelumFinal(): array
    {
        return array_values(array_map(
            fn (StatusPengirimanEmail $status): string => $status->value,
            array_filter(
                StatusPengirimanEmail::cases(),
                fn (StatusPengirimanEmail $status): bool => ! $status->final()
                    && $status !== StatusPengirimanEmail::Terjadwal,
            ),
        ));
    }
}
