<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Pemasaran\Domain\ValueObjects\FilterGrowth;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/** Biaya akuisisi: belanja kampanye dibagi pelanggan baru yang lahir darinya (MARKETING.md 5, 13). */
final class PenghitungCacKampanye
{
    // Pelanggan hanya tertaut ke kampanye, bukan ke channel, jadi kampanye multi-channel dilaporkan terpisah.

    /** Alasan CAC satu channel tidak punya angka; ditampilkan apa adanya alih-alih nol. */
    public const TANPA_PELANGGAN = 'Ada belanja, tetapi belum ada pelanggan baru yang lahir darinya.';

    public const TANPA_BIAYA = 'Ada pelanggan baru, tetapi belum ada belanja yang tercatat.';

    /**
     * CAC tiap channel, dihitung hanya dari kampanye berchannel tunggal.
     *
     * @return list<array{Channel: string, Biaya: float, Pelanggan: int, Cac: float|null, Alasan: string|null}>
     */
    public function perChannel(FilterGrowth $filter): array
    {
        $tunggal = $this->kampanyeChannelTunggal($filter);

        $biaya = [];
        $pelanggan = [];

        foreach ($this->biayaPerKampanye($filter) as $kampanyeId => $perChannel) {
            if (! isset($tunggal[$kampanyeId])) {
                continue;
            }

            foreach ($perChannel as $channel => $jumlah) {
                $biaya[$channel] = ($biaya[$channel] ?? 0.0) + $jumlah;
            }
        }

        foreach ($this->pelangganPerKampanye($filter) as $kampanyeId => $jumlah) {
            if (! isset($tunggal[$kampanyeId])) {
                continue;
            }

            $channel = $tunggal[$kampanyeId];
            $pelanggan[$channel] = ($pelanggan[$channel] ?? 0) + $jumlah;
        }

        $hasil = [];

        foreach (array_unique([...array_keys($biaya), ...array_keys($pelanggan)]) as $channel) {
            $hasil[] = $this->baris($channel, $biaya[$channel] ?? 0.0, $pelanggan[$channel] ?? 0);
        }

        usort($hasil, fn (array $a, array $b): int => $b['Biaya'] <=> $a['Biaya']);

        return $hasil;
    }

    /**
     * Kampanye multi-channel: biayanya nyata, tetapi pelanggannya tidak dapat dipecah per channel.
     *
     * @return array{Biaya: float, Pelanggan: int, Kampanye: list<string>}
     */
    public function takTerpecah(FilterGrowth $filter): array
    {
        $tunggal = $this->kampanyeChannelTunggal($filter);
        $biaya = 0.0;
        $kampanye = [];

        foreach ($this->biayaPerKampanye($filter) as $kampanyeId => $perChannel) {
            if (isset($tunggal[$kampanyeId])) {
                continue;
            }

            $biaya += array_sum($perChannel);
            $kampanye[] = $kampanyeId;
        }

        $pelanggan = 0;

        foreach ($this->pelangganPerKampanye($filter) as $kampanyeId => $jumlah) {
            if (isset($tunggal[$kampanyeId])) {
                continue;
            }

            $pelanggan += $jumlah;
            $kampanye[] = $kampanyeId;
        }

        $kode = $this->kodeKampanye(array_values(array_unique($kampanye)));

        return ['Biaya' => round($biaya, 2), 'Pelanggan' => $pelanggan, 'Kampanye' => $kode];
    }

    /** CAC gabungan seluruh kampanye; inilah satu angka yang dipasang di kartu KPI. */
    public function gabungan(FilterGrowth $filter): float
    {
        $biaya = 0.0;

        foreach ($this->biayaPerKampanye($filter) as $perChannel) {
            $biaya += array_sum($perChannel);
        }

        $pelanggan = array_sum($this->pelangganPerKampanye($filter));

        return $pelanggan === 0 ? 0.0 : round($biaya / $pelanggan, 2);
    }

    /**
     * Belanja tiap kampanye pada rentang ini, dipecah menurut channel yang dibayari.
     *
     * @return array<string, array<string, float>>
     */
    public function biayaPerKampanye(FilterGrowth $filter): array
    {
        $baris = DB::table('KampanyeBiaya')
            ->whereBetween('Tanggal', [$filter->tanggalDari(), $filter->tanggalSampai()])
            ->when($filter->kampanye !== null, fn ($kueri) => $kueri->whereIn(
                'KampanyeId',
                fn (Builder $sub) => $sub->select('Id')->from('Kampanye')->where('Kode', $filter->kampanye),
            ))
            ->groupBy('KampanyeId', 'Channel')
            ->selectRaw('KampanyeId, Channel, sum(Jumlah) as total')
            ->get();

        $hasil = [];

        foreach ($baris as $satu) {
            $hasil[(string) $satu->KampanyeId][(string) $satu->Channel] = round((float) $satu->total, 2);
        }

        return $hasil;
    }

    /**
     * Pelanggan baru tiap kampanye: trial yang berkonversi di rentang ini, menurut sentuhan pertamanya.
     *
     * @return array<string, int>
     */
    public function pelangganPerKampanye(FilterGrowth $filter): array
    {
        $baris = DB::table('Trial')
            ->join(
                'AttributionPemasaran',
                'AttributionPemasaran.PengenalPengunjung', '=', 'Trial.PengenalPengunjung',
            )
            ->whereNotNull('Trial.KonversiPada')
            ->whereBetween('Trial.KonversiPada', [$filter->dari, $filter->sampai])
            ->whereNotNull('AttributionPemasaran.KampanyeIdPertama')
            ->when($filter->kampanye !== null, fn ($kueri) => $kueri->whereIn(
                'AttributionPemasaran.KampanyeIdPertama',
                fn (Builder $sub) => $sub->select('Id')->from('Kampanye')->where('Kode', $filter->kampanye),
            ))
            ->groupBy('AttributionPemasaran.KampanyeIdPertama')
            ->selectRaw('AttributionPemasaran.KampanyeIdPertama as kampanye, count(*) as total')
            ->get();

        $hasil = [];

        foreach ($baris as $satu) {
            $hasil[(string) $satu->kampanye] = (int) $satu->total;
        }

        return $hasil;
    }

    /** @return array{Channel: string, Biaya: float, Pelanggan: int, Cac: float|null, Alasan: string|null} */
    private function baris(string $channel, float $biaya, int $pelanggan): array
    {
        $biaya = round($biaya, 2);

        if ($pelanggan === 0) {
            return [
                'Channel' => $channel, 'Biaya' => $biaya, 'Pelanggan' => 0,
                'Cac' => null, 'Alasan' => self::TANPA_PELANGGAN,
            ];
        }

        if ($biaya <= 0.0) {
            return [
                'Channel' => $channel, 'Biaya' => $biaya, 'Pelanggan' => $pelanggan,
                'Cac' => null, 'Alasan' => self::TANPA_BIAYA,
            ];
        }

        return [
            'Channel' => $channel, 'Biaya' => $biaya, 'Pelanggan' => $pelanggan,
            'Cac' => round($biaya / $pelanggan, 2), 'Alasan' => null,
        ];
    }

    /**
     * Kampanye yang hanya punya satu channel; hanya untuk merekalah pelanggan dapat ditautkan ke channel.
     *
     * @return array<string, string>
     */
    private function kampanyeChannelTunggal(FilterGrowth $filter): array
    {
        $baris = DB::table('KampanyeChannel')
            ->when($filter->kampanye !== null, fn ($kueri) => $kueri->whereIn(
                'KampanyeId',
                fn (Builder $sub) => $sub->select('Id')->from('Kampanye')->where('Kode', $filter->kampanye),
            ))
            ->groupBy('KampanyeId')
            ->havingRaw('count(*) = 1')
            ->selectRaw('KampanyeId, min(Channel) as channel')
            ->get();

        $hasil = [];

        foreach ($baris as $satu) {
            $hasil[(string) $satu->KampanyeId] = (string) $satu->channel;
        }

        return $hasil;
    }

    /**
     * @param  list<string>  $id
     * @return list<string>
     */
    private function kodeKampanye(array $id): array
    {
        if ($id === []) {
            return [];
        }

        return array_values(array_map(
            strval(...),
            DB::table('Kampanye')->whereIn('Id', $id)->orderBy('Kode')->pluck('Kode')->all(),
        ));
    }
}
