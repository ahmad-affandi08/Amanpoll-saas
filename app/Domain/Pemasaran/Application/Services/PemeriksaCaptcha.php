<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Verifikasi CAPTCHA formulir publik (MARKETING.md 10). */
final class PemeriksaCaptcha
{
    private const BATAS_DETIK = 5;

    public function namaField(): string
    {
        return (string) config('amanpoll.pemasaran.captcha.nama_field', 'cf-turnstile-response');
    }

    public function pastikanSah(?string $token, ?string $alamatIp = null): void
    {
        $rahasia = config('amanpoll.pemasaran.captcha.rahasia');

        if (! is_string($rahasia) || $rahasia === '') {
            throw new AturanBisnisDilanggar(
                'CAPTCHA dinyalakan pada formulir ini tetapi kuncinya belum dipasang.',
            );
        }

        if ($token === null || trim($token) === '') {
            throw new AturanBisnisDilanggar('Verifikasi CAPTCHA belum diselesaikan.');
        }

        if (! $this->sahMenurutPenyedia($rahasia, $token, $alamatIp)) {
            throw new AturanBisnisDilanggar('Verifikasi CAPTCHA gagal.');
        }
    }

    private function sahMenurutPenyedia(string $rahasia, string $token, ?string $alamatIp): bool
    {
        $endpoint = (string) config('amanpoll.pemasaran.captcha.endpoint');

        try {
            $respons = Http::timeout(self::BATAS_DETIK)->asForm()->post($endpoint, array_filter([
                'secret' => $rahasia,
                'response' => $token,
                'remoteip' => $alamatIp,
            ], fn (?string $nilai): bool => $nilai !== null));
        } catch (Throwable $galat) {
            Log::warning('Verifikasi CAPTCHA tidak dapat dihubungi.', ['pesan' => $galat->getMessage()]);

            return false;
        }

        return $respons->successful() && $respons->json('success') === true;
    }
}
