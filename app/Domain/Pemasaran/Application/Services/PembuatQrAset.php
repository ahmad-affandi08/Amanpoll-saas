<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/** Generator QR aset untuk tool publik (MARKETING.md 10). */
final class PembuatQrAset
{
    /** Satu lembar label sekali cetak; batasnya menjaga endpoint anonim ini tetap murah. */
    public const MAKS_KODE = 50;

    public const MAKS_PANJANG_KODE = 120;

    private const UKURAN_PIKSEL = 220;

    /**
     * @param  list<string>  $kode
     * @return list<array{Kode: string, Svg: string}>
     */
    public function untuk(array $kode): array
    {
        $bersih = $this->rapikan($kode);

        if ($bersih === []) {
            throw new AturanBisnisDilanggar('Tidak ada kode aset yang dapat dibuatkan QR.');
        }

        if (count($bersih) > self::MAKS_KODE) {
            throw new AturanBisnisDilanggar(
                'Sekali buat paling banyak '.self::MAKS_KODE.' kode aset.',
            );
        }

        $penulis = new Writer(new ImageRenderer(
            new RendererStyle(self::UKURAN_PIKSEL, 1),
            new SvgImageBackEnd,
        ));

        return array_map(
            fn (string $satu): array => ['Kode' => $satu, 'Svg' => $penulis->writeString($satu)],
            $bersih,
        );
    }

    /**
     * Kode kembar hanya menghasilkan label kembar, jadi disatukan lebih dulu.
     *
     * @param  list<string>  $kode
     * @return list<string>
     */
    private function rapikan(array $kode): array
    {
        $bersih = [];

        foreach ($kode as $satu) {
            $satu = trim($satu);

            if ($satu === '') {
                continue;
            }

            if (mb_strlen($satu) > self::MAKS_PANJANG_KODE) {
                throw new AturanBisnisDilanggar(
                    'Kode aset paling panjang '.self::MAKS_PANJANG_KODE.' karakter.',
                );
            }

            $bersih[$satu] = $satu;
        }

        return array_values($bersih);
    }
}
