<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Qr;

use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * Encoder QR kode aset, dipakai tool publik (MARKETING.md 10) dan cetak label
 * aset tenant.
 *
 * Batas jumlahnya tidak dipatok di sini karena kedua pemakainya punya alasan
 * berbeda: tool publik menahan endpoint anonim tetap murah, sedangkan cetak
 * label dibatasi oleh apa yang masuk akal dalam satu kali cetak.
 */
final class PembuatQrAset
{
    /** Batas panjang kode; di atas ini QR jadi terlalu rapat untuk dipindai dari label kecil. */
    public const MAKS_PANJANG_KODE = 120;

    private const UKURAN_PIKSEL = 220;

    /**
     * @param  list<string>  $kode
     * @param  int  $maksKode  Batas jumlah kode yang ditentukan pemanggil.
     * @return list<array{Kode: string, Svg: string}>
     */
    public function untuk(array $kode, int $maksKode): array
    {
        $bersih = $this->rapikan($kode);

        if ($bersih === []) {
            throw new AturanBisnisDilanggar('Tidak ada kode aset yang dapat dibuatkan QR.');
        }

        if (count($bersih) > $maksKode) {
            throw new AturanBisnisDilanggar(
                'Sekali buat paling banyak '.$maksKode.' kode aset.',
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
