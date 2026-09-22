<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Langganan\Application\Services\LayananKebijakanTenggang;
use App\Domain\Langganan\Domain\Enums\SiklusLangganan;
use App\Domain\Langganan\Domain\Enums\StatusTagihanLangganan;
use App\Domain\Langganan\Infrastructure\Persistence\Models\Langganan;
use App\Domain\Langganan\Infrastructure\Persistence\Models\PaketLangganan;
use App\Domain\Langganan\Infrastructure\Persistence\Models\TagihanLangganan;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;

/** Penerbitan tagihan periode langganan (22.06). */
final class TerbitkanTagihanLangganan
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananAudit $audit,
        private readonly LayananKebijakanTenggang $kebijakan,
    ) {}

    public function jalankan(Langganan $langganan, ?CarbonImmutable $pada = null): TagihanLangganan
    {
        return $this->transaksi->jalankan(function () use ($langganan, $pada): TagihanLangganan {
            $pada ??= CarbonImmutable::now();
            $paket = PaketLangganan::query()->find((string) $langganan->PaketLanggananId)
                ?? throw new AturanBisnisDilanggar('Langganan tidak menunjuk paket mana pun.');

            $siklus = SiklusLangganan::from((string) $langganan->Siklus);
            $periodeMulai = $this->periodeMulai($langganan, $pada);
            $periodeSelesai = $siklus->akhirPeriodeSetelah($periodeMulai)->subDay();

            $adaYangSama = TagihanLangganan::query()
                ->withoutGlobalScopes()
                ->where('LanggananId', $langganan->Id)
                ->where('PeriodeMulai', $periodeMulai->toDateString())
                ->whereNot('Status', StatusTagihanLangganan::Dibatalkan->value)
                ->first();

            if ($adaYangSama !== null) {
                return $adaYangSama;
            }

            $subtotal = $siklus === SiklusLangganan::Tahunan
                ? (float) $paket->HargaTahunan
                : (float) $paket->HargaBulanan;
            $pajak = round($subtotal * ((float) config('amanpoll.langganan.pajak_persen', 0)) / 100, 2);

            $tagihan = TagihanLangganan::create([
                'OrganisasiId' => $langganan->OrganisasiId,
                'LanggananId' => $langganan->Id,
                'Nomor' => $this->nomorBerikutnya($pada),
                'PeriodeMulai' => $periodeMulai->toDateString(),
                'PeriodeSelesai' => $periodeSelesai->toDateString(),
                'JatuhTempo' => $pada->addDays($this->kebijakan->hariJatuhTempo())->toDateString(),
                'Subtotal' => $subtotal,
                'Pajak' => $pajak,
                'Total' => round($subtotal + $pajak, 2),
                'Status' => StatusTagihanLangganan::BelumDibayar->value,
            ]);

            $this->audit->catat('TagihanLangganan.Diterbitkan', 'TagihanLangganan', $tagihan->Id, dataSesudah: [
                'Nomor' => $tagihan->Nomor,
                'Total' => (float) $tagihan->Total,
                'PeriodeMulai' => $periodeMulai->toDateString(),
            ]);

            return $tagihan;
        });
    }

    /** Periode yang ditagih adalah periode yang akan dimulai setelah periode berjalan berakhir. */
    private function periodeMulai(Langganan $langganan, CarbonImmutable $pada): CarbonImmutable
    {
        $berakhir = $langganan->BerakhirPada;

        return $berakhir === null
            ? $pada->startOfDay()
            : CarbonImmutable::parse($berakhir)->startOfDay()->addDay();
    }

    /** Nomor berurut per bulan. */
    private function nomorBerikutnya(CarbonImmutable $pada): string
    {
        $awalan = 'INV-'.$pada->format('Ym').'-';

        $terakhir = TagihanLangganan::query()
            ->withoutGlobalScopes()
            ->where('Nomor', 'like', $awalan.'%')
            ->orderByDesc('Nomor')
            ->value('Nomor');

        $urutan = $terakhir === null ? 1 : ((int) substr((string) $terakhir, -5)) + 1;

        return $awalan.str_pad((string) $urutan, 5, '0', STR_PAD_LEFT);
    }
}
