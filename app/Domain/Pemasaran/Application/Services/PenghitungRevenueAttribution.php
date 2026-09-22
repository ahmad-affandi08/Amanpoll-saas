<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Langganan\Domain\Enums\StatusPembayaranLangganan;
use App\Domain\Pemasaran\Domain\Enums\ModelAttribution;
use App\Domain\Pemasaran\Domain\KatalogKonfigurasiPemasaran;
use App\Domain\Pemasaran\Domain\ValueObjects\BobotSentuhan;
use App\Domain\Pemasaran\Domain\ValueObjects\FilterGrowth;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Revenue per channel menurut model attribution yang dipilih. Tiap pembayaran
 * dibagi kepada sentuhan-sentuhan pengunjungnya, dan jumlah bobotnya tepat satu
 * sehingga totalnya tidak pernah melebihi uang yang benar-benar masuk (Gate 38.10).
 */
final class PenghitungRevenueAttribution
{
    /** Pembayaran yang pengunjungnya tidak punya sentuhan sama sekali tetap dilaporkan, bukan hilang. */
    public const TANPA_SENTUHAN = 'Tanpa Sentuhan';

    public function __construct(
        private readonly PembacaSentuhan $pembaca,
        private readonly PembagiBobotAttribution $pembagi,
        private readonly LayananKonfigurasiPemasaran $konfigurasi,
    ) {}

    public function modelAktif(): ModelAttribution
    {
        $tersimpan = $this->konfigurasi->ambil(KatalogKonfigurasiPemasaran::ATTRIBUTION_MODEL);

        return ModelAttribution::tryFrom(is_string($tersimpan) ? $tersimpan : '')
            ?? ModelAttribution::Pertama;
    }

    public function paruhHari(): int
    {
        return max(1, $this->konfigurasi->angka(KatalogKonfigurasiPemasaran::ATTRIBUTION_PARUH_HARI));
    }

    /**
     * Revenue per channel menurut satu model.
     *
     * @return array<string, float>
     */
    public function perChannel(FilterGrowth $filter, ?ModelAttribution $model = null): array
    {
        $model ??= $this->modelAktif();
        $hasil = [];

        foreach ($this->pembayaran($filter) as $satu) {
            foreach ($this->bagianPembayaran($satu, $model) as $channel => $porsi) {
                $hasil[$channel] = ($hasil[$channel] ?? 0.0) + $porsi;
            }
        }

        foreach ($hasil as $channel => $nilai) {
            $hasil[$channel] = round($nilai, 2);
        }

        // Channel yang tidak kebagian apa pun tidak perlu muncul sebagai baris nol.
        $hasil = array_filter($hasil, fn (float $nilai): bool => $nilai > 0.0);

        if ($filter->channel !== null) {
            $hasil = array_intersect_key($hasil, [$filter->channel => true]);
        }

        arsort($hasil);

        return $hasil;
    }

    /**
     * Rincian bobot satu pengunjung, dipakai konsol untuk memperlihatkan
     * perjalanannya sendiri, bukan hanya angka jadinya.
     *
     * @return list<BobotSentuhan>
     */
    public function bobotPengunjung(
        string $pengenalPengunjung,
        CarbonImmutable $konversiPada,
        ?ModelAttribution $model = null,
    ): array {
        return $this->pembagi->bagi(
            $this->pembaca->untuk($pengenalPengunjung, $konversiPada),
            $model ?? $this->modelAktif(),
            $konversiPada,
            $this->paruhHari(),
        );
    }

    /**
     * Porsi satu pembayaran per channel.
     *
     * @param  object{pengenal: ?string, jumlah: float|int|string, dibayar: string}  $pembayaran
     * @return array<string, float>
     */
    private function bagianPembayaran(object $pembayaran, ModelAttribution $model): array
    {
        $jumlah = (float) $pembayaran->jumlah;
        $pengenal = $pembayaran->pengenal;

        if ($pengenal === null || $pengenal === '') {
            return [self::TANPA_SENTUHAN => $jumlah];
        }

        $konversiPada = CarbonImmutable::parse($pembayaran->dibayar);
        $bobot = $this->bobotPengunjung($pengenal, $konversiPada, $model);

        if ($bobot === []) {
            return [self::TANPA_SENTUHAN => $jumlah];
        }

        $porsi = [];

        foreach ($bobot as $satu) {
            $channel = $satu->sentuhan->sumber;
            $porsi[$channel] = ($porsi[$channel] ?? 0.0) + $satu->porsiDari($jumlah);
        }

        return $porsi;
    }

    /** @return list<object{pengenal: ?string, jumlah: float|int|string, dibayar: string}> */
    private function pembayaran(FilterGrowth $filter): array
    {
        /** @var list<object{pengenal: ?string, jumlah: float|int|string, dibayar: string}> $baris */
        $baris = DB::table('PembayaranLangganan')
            ->join('Trial', 'Trial.OrganisasiId', '=', 'PembayaranLangganan.OrganisasiId')
            ->where('PembayaranLangganan.Status', StatusPembayaranLangganan::Berhasil->value)
            ->whereBetween('PembayaranLangganan.DibayarPada', [$filter->dari, $filter->sampai])
            ->orderBy('PembayaranLangganan.DibayarPada')
            ->get([
                'Trial.PengenalPengunjung as pengenal',
                'PembayaranLangganan.Jumlah as jumlah',
                'PembayaranLangganan.DibayarPada as dibayar',
            ])
            ->all();

        return $baris;
    }
}
