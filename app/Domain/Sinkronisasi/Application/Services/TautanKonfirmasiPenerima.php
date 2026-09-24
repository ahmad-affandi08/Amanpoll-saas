<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Application\Services;

use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

/**
 * Tautan QR konfirmasi penerima (PRD 8.22, cara 2).
 *
 * Isinya rute `lapangan.konfirmasi-penerima` bertanda tangan server (HMAC `APP_KEY`)
 * dengan masa berlaku `amanpoll.konfirmasi_penerima.menit_berlaku_qr`. Tanda tangannya
 * relatif (tanpa host) supaya tetap sah di balik proxy; id perintah kerja ada di
 * jalur, sehingga token satu tiket tidak bisa dipakai untuk tiket lain, dan tenancy
 * pada pencarian tiket menolak pemakaian dari organisasi lain.
 */
final class TautanKonfirmasiPenerima
{
    private const UKURAN_PIKSEL = 280;

    /** @return array{Url: string, Svg: string, BerlakuSampai: string} */
    public function buat(PerintahKerja $perintahKerja): array
    {
        $berlakuSampai = CarbonImmutable::now()->addMinutes(max(1, (int) config('amanpoll.konfirmasi_penerima.menit_berlaku_qr', 10)));
        $jalur = URL::temporarySignedRoute('lapangan.konfirmasi-penerima', $berlakuSampai, ['perintahKerja' => $perintahKerja->Id], absolute: false);
        $url = url($jalur);

        $penulis = new Writer(new ImageRenderer(new RendererStyle(self::UKURAN_PIKSEL, 1), new SvgImageBackEnd));

        return [
            'Url' => $url,
            'Svg' => $penulis->writeString($url),
            'BerlakuSampai' => $berlakuSampai->toIso8601String(),
        ];
    }

    /** Tanda tangan cocok dengan jalur dan kuerinya (belum tentu masih berlaku). */
    public function sah(Request $request): bool
    {
        return URL::hasCorrectSignature($request, false);
    }

    public function kedaluwarsa(Request $request): bool
    {
        return ! URL::signatureHasNotExpired($request);
    }
}
