<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Pemasaran\Domain\KatalogKonfigurasiPemasaran;
use App\Domain\Pemasaran\Domain\ValueObjects\AsalKunjungan;
use App\Domain\Pemasaran\Domain\ValueObjects\Sentuhan;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Perjalanan sentuhan seorang pengunjung, lengkap dan berurut. Sentuhannya
 * tidak disimpan di tabel tersendiri: tiap kunjungan sudah punya barisnya di
 * `SesiPengunjung` beserta UTM-nya, jadi yang kurang hanya cara membacanya
 * sebagai satu perjalanan (MARKETING.md 14, 24).
 */
final class PembacaSentuhan
{
    public function __construct(private readonly LayananKonfigurasiPemasaran $konfigurasi) {}

    /**
     * Seluruh sentuhan satu pengunjung sampai waktu konversinya.
     *
     * @return list<Sentuhan>
     */
    public function untuk(string $pengenalPengunjung, CarbonImmutable $konversiPada): array
    {
        return $this->banyak([$pengenalPengunjung], $konversiPada)[$pengenalPengunjung] ?? [];
    }

    /**
     * Sentuhan banyak pengunjung sekaligus; dashboard membacanya per halaman,
     * bukan satu kueri per pengunjung.
     *
     * @param  list<string>  $pengenalPengunjung
     * @return array<string, list<Sentuhan>>
     */
    public function banyak(array $pengenalPengunjung, CarbonImmutable $konversiPada): array
    {
        $pengenal = array_values(array_unique(array_filter($pengenalPengunjung)));

        if ($pengenal === []) {
            return [];
        }

        $baris = DB::table('SesiPengunjung')
            ->leftJoin('UtmPemasaran', 'UtmPemasaran.SesiPengunjungId', '=', 'SesiPengunjung.Id')
            ->whereIn('SesiPengunjung.PengenalPengunjung', $pengenal)
            ->whereBetween('SesiPengunjung.DimulaiPada', [$this->awalJendela($konversiPada), $konversiPada])
            ->orderBy('SesiPengunjung.DimulaiPada')
            ->orderBy('SesiPengunjung.Id')
            ->get([
                'SesiPengunjung.PengenalPengunjung as pengenal',
                'SesiPengunjung.Referrer as referrer',
                'SesiPengunjung.DimulaiPada as pada',
                'UtmPemasaran.Source as sumber',
                'UtmPemasaran.Medium as medium',
                'UtmPemasaran.Campaign as kampanye',
                'UtmPemasaran.KampanyeId as kampanyeId',
            ]);

        $hasil = [];

        foreach ($baris as $satu) {
            $kunci = (string) $satu->pengenal;
            $sentuhan = $this->dariBaris($satu);
            $terakhir = $hasil[$kunci][array_key_last($hasil[$kunci] ?? [])] ?? null;

            // Kunjungan berurutan dari sumber yang sama adalah satu sentuhan yang berlanjut.
            if ($terakhir instanceof Sentuhan && $terakhir->kunci() === $sentuhan->kunci()) {
                continue;
            }

            $hasil[$kunci][] = $sentuhan;
        }

        return [...$hasil, ...$this->cadangan(array_values(array_diff($pengenal, array_keys($hasil))))];
    }

    /**
     * Pengunjung yang riwayat kunjungannya sudah tidak ada lagi tetap punya
     * sentuhan yang pernah tercatat di AttributionPemasaran. Yang dipakai hanya
     * pengunjung tanpa satu pun baris sesi: kalau sesinya ada tetapi seluruhnya
     * di luar jendela, itu memang bukan sentuhan yang boleh diperhitungkan.
     *
     * @param  list<string>  $pengenal
     * @return array<string, list<Sentuhan>>
     */
    private function cadangan(array $pengenal): array
    {
        if ($pengenal === []) {
            return [];
        }

        $punyaSesi = DB::table('SesiPengunjung')
            ->whereIn('PengenalPengunjung', $pengenal)
            ->distinct()
            ->pluck('PengenalPengunjung')
            ->all();

        $tanpaSesi = array_values(array_diff($pengenal, $punyaSesi));

        if ($tanpaSesi === []) {
            return [];
        }

        $hasil = [];

        foreach ($this->attributionTercatat($tanpaSesi) as $pengenalBaris => $baris) {
            $sentuhan = $this->dariAttribution($baris);

            if ($sentuhan !== []) {
                $hasil[$pengenalBaris] = $sentuhan;
            }
        }

        return $hasil;
    }

    /**
     * @param  list<string>  $pengenal
     * @return array<string, object>
     */
    private function attributionTercatat(array $pengenal): array
    {
        /** @var array<string, object> $baris */
        $baris = DB::table('AttributionPemasaran')
            ->whereIn('PengenalPengunjung', $pengenal)
            ->whereNotNull('SentuhanPertamaPada')
            ->get([
                'PengenalPengunjung as pengenal',
                'SumberPertama as sumberPertama',
                'MediumPertama as mediumPertama',
                'KampanyePertama as kampanyePertama',
                'KampanyeIdPertama as kampanyeIdPertama',
                'SentuhanPertamaPada as pertamaPada',
                'SumberTerakhir as sumberTerakhir',
                'MediumTerakhir as mediumTerakhir',
                'KampanyeTerakhir as kampanyeTerakhir',
                'KampanyeIdTerakhir as kampanyeIdTerakhir',
                'SentuhanTerakhirPada as terakhirPada',
            ])
            ->keyBy('pengenal')
            ->all();

        return $baris;
    }

    /**
     * Barisnya menyimpan dua sentuhan; keduanya dipakai bila memang berbeda.
     *
     * @return list<Sentuhan>
     */
    private function dariAttribution(object $baris): array
    {
        /** @var object{sumberPertama: ?string, mediumPertama: ?string, kampanyePertama: ?string, kampanyeIdPertama: ?string, pertamaPada: ?string, sumberTerakhir: ?string, mediumTerakhir: ?string, kampanyeTerakhir: ?string, kampanyeIdTerakhir: ?string, terakhirPada: ?string} $baris */
        if ($baris->sumberPertama === null || $baris->pertamaPada === null) {
            return [];
        }

        $pertama = new Sentuhan(
            sumber: $baris->sumberPertama,
            medium: $baris->mediumPertama,
            kampanye: $baris->kampanyePertama,
            kampanyeId: $baris->kampanyeIdPertama,
            pada: CarbonImmutable::parse($baris->pertamaPada),
        );

        if ($baris->sumberTerakhir === null || $baris->terakhirPada === null) {
            return [$pertama];
        }

        $terakhir = new Sentuhan(
            sumber: $baris->sumberTerakhir,
            medium: $baris->mediumTerakhir,
            kampanye: $baris->kampanyeTerakhir,
            kampanyeId: $baris->kampanyeIdTerakhir,
            pada: CarbonImmutable::parse($baris->terakhirPada),
        );

        return $pertama->kunci() === $terakhir->kunci() ? [$pertama] : [$pertama, $terakhir];
    }

    /** Sentuhan yang lebih tua dari jendela attribution tidak lagi diperhitungkan. */
    public function awalJendela(CarbonImmutable $konversiPada): CarbonImmutable
    {
        $hari = max(1, $this->konfigurasi->angka(KatalogKonfigurasiPemasaran::ATTRIBUTION_JENDELA_HARI));

        return $konversiPada->subDays($hari);
    }

    private function dariBaris(object $baris): Sentuhan
    {
        /** @var object{referrer: ?string, pada: string, sumber: ?string, medium: ?string, kampanye: ?string, kampanyeId: ?string} $baris */
        return new Sentuhan(
            sumber: AsalKunjungan::sumber($baris->sumber, $baris->referrer),
            medium: AsalKunjungan::medium($baris->medium, $baris->sumber, $baris->referrer),
            kampanye: $baris->kampanye,
            kampanyeId: $baris->kampanyeId,
            pada: CarbonImmutable::parse($baris->pada),
        );
    }
}
