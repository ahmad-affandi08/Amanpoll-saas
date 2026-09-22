<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Tindakan;

use App\Domain\IntegrasiAudit\Application\Services\LayananPanggilanBalikWeb;
use App\Domain\Pemasaran\Domain\Contracts\TindakanOtomasi;
use App\Domain\Pemasaran\Domain\ValueObjects\KonteksOtomasi;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Support\Facades\Http;

/**
 * Memanggil webhook luar dengan tanda tangan yang sama seperti FASE 19.
 *
 * Rahasianya dari environment, bukan dari konfigurasi langkah: konfigurasi
 * langkah tersimpan sebagai JSON biasa dan terbaca siapa pun yang membuka
 * konsol. Kegagalan dilempar agar ditangani percobaan ulang mesin otomasi,
 * bukan diam-diam ditelan.
 */
final class TindakanWebhook implements TindakanOtomasi
{
    public function kode(): string
    {
        return 'Webhook';
    }

    public function label(): string
    {
        return 'Panggil webhook';
    }

    /** @return array<string, mixed> */
    public function aturan(): array
    {
        return [
            'Url' => ['required', 'url', 'max:500'],
            'Peristiwa' => ['nullable', 'string', 'max:120'],
        ];
    }

    /** @param array<string, mixed> $konfigurasi */
    public function jalankan(KonteksOtomasi $konteks, array $konfigurasi): string
    {
        $url = (string) ($konfigurasi['Url'] ?? '');
        $rahasia = (string) config('amanpoll.pemasaran.webhook_rahasia', '');

        if ($rahasia === '') {
            throw new AturanBisnisDilanggar(
                'Rahasia tanda tangan webhook pemasaran belum disetel; webhook tanpa tanda tangan tidak dikirim.',
            );
        }

        $badan = (string) json_encode($this->muatan($konteks, $konfigurasi), JSON_UNESCAPED_SLASHES);

        $respons = Http::withHeaders([
            'Content-Type' => 'application/json',
            LayananPanggilanBalikWeb::HEADER_TANDA_TANGAN => 'sha256='.hash_hmac('sha256', $badan, $rahasia),
            LayananPanggilanBalikWeb::HEADER_PERISTIWA => (string) ($konfigurasi['Peristiwa'] ?? 'OtomasiPemasaran'),
            LayananPanggilanBalikWeb::HEADER_PENGIRIMAN => $konteks->kunciLangkah,
        ])->timeout(10)->withBody($badan, 'application/json')->post($url);

        if (! $respons->successful()) {
            throw new AturanBisnisDilanggar("Webhook membalas status {$respons->status()}.");
        }

        return "Webhook {$url} membalas {$respons->status()}.";
    }

    /**
     * @param  array<string, mixed>  $konfigurasi
     * @return array<string, mixed>
     */
    private function muatan(KonteksOtomasi $konteks, array $konfigurasi): array
    {
        return [
            'peristiwa' => $konfigurasi['Peristiwa'] ?? 'OtomasiPemasaran',
            'kunci' => $konteks->kunciLangkah,
            'prospek' => $konteks->prospek === null ? null : [
                'id' => $konteks->prospek->Id,
                'nama' => $konteks->prospek->Nama,
                'email' => $konteks->prospek->Email,
            ],
            'organisasi_id' => $konteks->organisasiId,
            'data' => $konteks->dataPeristiwa,
        ];
    }
}
