<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Jobs;

use App\Domain\Langganan\Domain\Enums\StatusPembayaranLangganan;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\MetrikKampanye;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Lima kueri agregat untuk seluruh kampanye sekaligus, hasilnya disimpan agar dashboard tidak menghitung ulang (MARKETING.md 5, 37.05). */
final class HitungMetrikKampanye implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly ?string $tanggal = null)
    {
        $this->onConnection('database');
    }

    public function handle(): void
    {
        $hari = $this->tanggal === null
            ? CarbonImmutable::now()->subDay()->startOfDay()
            : CarbonImmutable::parse($this->tanggal)->startOfDay();

        $akhir = $hari->endOfDay();
        $kunci = $this->kunciKampanye();

        $ringkasan = [];

        $this->serap($ringkasan, $this->visitor($hari, $akhir), 'Visitor');
        $this->serap($ringkasan, $this->prospek($hari, $akhir), 'Lead');
        $this->serap($ringkasan, $this->trial($hari, $akhir, 'MulaiPada'), 'Trial');
        $this->serap($ringkasan, $this->trial($hari, $akhir, 'TeraktivasiPada'), 'Teraktivasi');
        $this->serap($ringkasan, $this->trial($hari, $akhir, 'KonversiPada'), 'Bayar');
        $this->serap($ringkasan, $this->revenue($hari, $akhir), 'Revenue');

        foreach ($ringkasan as $baris) {
            $this->simpan($hari, $baris, $kunci);
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $ringkasan
     * @param  array<string, array{kampanye: string|null, channel: string|null, nilai: float}>  $baris
     */
    private function serap(array &$ringkasan, array $baris, string $kolom): void
    {
        foreach ($baris as $kunci => $satu) {
            $ringkasan[$kunci] ??= [
                'kampanye' => $satu['kampanye'],
                'channel' => $satu['channel'],
                'Visitor' => 0, 'Lead' => 0, 'Trial' => 0, 'Teraktivasi' => 0, 'Bayar' => 0, 'Revenue' => 0.0,
            ];

            $ringkasan[$kunci][$kolom] = $satu['nilai'];
        }
    }

    /** @return array<string, array{kampanye: string|null, channel: string|null, nilai: float}> */
    private function visitor(CarbonImmutable $dari, CarbonImmutable $sampai): array
    {
        $baris = DB::table('SesiPengunjung')
            ->leftJoin(
                'AttributionPemasaran',
                'AttributionPemasaran.PengenalPengunjung', '=', 'SesiPengunjung.PengenalPengunjung',
            )
            ->whereBetween('SesiPengunjung.DimulaiPada', [$dari, $sampai])
            ->groupBy('AttributionPemasaran.KampanyeIdPertama', 'AttributionPemasaran.SumberPertama')
            ->selectRaw(
                'AttributionPemasaran.KampanyeIdPertama as kampanye, AttributionPemasaran.SumberPertama as channel, '
                .'count(distinct SesiPengunjung.PengenalPengunjung) as nilai',
            )
            ->get();

        return $this->petakan($baris);
    }

    /** @return array<string, array{kampanye: string|null, channel: string|null, nilai: float}> */
    private function prospek(CarbonImmutable $dari, CarbonImmutable $sampai): array
    {
        $baris = DB::table('Prospek')
            ->leftJoin(
                'AttributionPemasaran',
                'AttributionPemasaran.PengenalPengunjung', '=', 'Prospek.PengenalPengunjung',
            )
            ->whereBetween('Prospek.DibuatPada', [$dari, $sampai])
            ->groupBy('AttributionPemasaran.KampanyeIdPertama', 'AttributionPemasaran.SumberPertama')
            ->selectRaw(
                'AttributionPemasaran.KampanyeIdPertama as kampanye, AttributionPemasaran.SumberPertama as channel, '
                .'count(*) as nilai',
            )
            ->get();

        return $this->petakan($baris);
    }

    /** @return array<string, array{kampanye: string|null, channel: string|null, nilai: float}> */
    private function trial(CarbonImmutable $dari, CarbonImmutable $sampai, string $kolom): array
    {
        $baris = DB::table('Trial')
            ->leftJoin(
                'AttributionPemasaran',
                'AttributionPemasaran.PengenalPengunjung', '=', 'Trial.PengenalPengunjung',
            )
            ->whereNotNull("Trial.{$kolom}")
            ->whereBetween("Trial.{$kolom}", [$dari, $sampai])
            ->groupBy('AttributionPemasaran.KampanyeIdPertama', 'AttributionPemasaran.SumberPertama')
            ->selectRaw(
                'AttributionPemasaran.KampanyeIdPertama as kampanye, AttributionPemasaran.SumberPertama as channel, '
                .'count(*) as nilai',
            )
            ->get();

        return $this->petakan($baris);
    }

    /** @return array<string, array{kampanye: string|null, channel: string|null, nilai: float}> */
    private function revenue(CarbonImmutable $dari, CarbonImmutable $sampai): array
    {
        $baris = DB::table('PembayaranLangganan')
            ->join('Trial', 'Trial.OrganisasiId', '=', 'PembayaranLangganan.OrganisasiId')
            ->leftJoin(
                'AttributionPemasaran',
                'AttributionPemasaran.PengenalPengunjung', '=', 'Trial.PengenalPengunjung',
            )
            ->where('PembayaranLangganan.Status', StatusPembayaranLangganan::Berhasil->value)
            ->whereBetween('PembayaranLangganan.DibayarPada', [$dari, $sampai])
            ->groupBy('AttributionPemasaran.KampanyeIdPertama', 'AttributionPemasaran.SumberPertama')
            ->selectRaw(
                'AttributionPemasaran.KampanyeIdPertama as kampanye, AttributionPemasaran.SumberPertama as channel, '
                .'sum(PembayaranLangganan.Jumlah) as nilai',
            )
            ->get();

        return $this->petakan($baris);
    }

    /**
     * @param  Collection<int, \stdClass>  $baris
     * @return array<string, array{kampanye: string|null, channel: string|null, nilai: float}>
     */
    private function petakan(Collection $baris): array
    {
        $hasil = [];

        foreach ($baris as $satu) {
            $kampanye = $satu->kampanye === null ? null : (string) $satu->kampanye;
            $channel = $satu->channel === null ? null : (string) $satu->channel;

            $hasil[$kampanye.'|'.$channel] = [
                'kampanye' => $kampanye,
                'channel' => $channel,
                'nilai' => (float) $satu->nilai,
            ];
        }

        return $hasil;
    }

    /**
     * @param  array<string, mixed>  $baris
     * @param  list<string>  $kunciKampanye
     */
    private function simpan(CarbonImmutable $hari, array $baris, array $kunciKampanye): void
    {
        $kampanye = $baris['kampanye'];

        // Kampanye yang sudah dihapus tetap punya jejak di attribution; metriknya dihitung tanpa tautan.
        if ($kampanye !== null && ! in_array($kampanye, $kunciKampanye, true)) {
            $kampanye = null;
        }

        MetrikKampanye::query()->updateOrCreate(
            [
                'Tanggal' => $hari->toDateString(),
                'KampanyeId' => $kampanye,
                'Channel' => $baris['channel'],
            ],
            [
                'Id' => (string) Str::ulid(),
                'Visitor' => (int) $baris['Visitor'],
                'Lead' => (int) $baris['Lead'],
                'Trial' => (int) $baris['Trial'],
                'Teraktivasi' => (int) $baris['Teraktivasi'],
                'Bayar' => (int) $baris['Bayar'],
                'Revenue' => round((float) $baris['Revenue'], 2),
                'DihitungPada' => CarbonImmutable::now(),
            ],
        );
    }

    /** @return list<string> */
    private function kunciKampanye(): array
    {
        return array_values(array_map(strval(...), DB::table('Kampanye')->pluck('Id')->all()));
    }
}
