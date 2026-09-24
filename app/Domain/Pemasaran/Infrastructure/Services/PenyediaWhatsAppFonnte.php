<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Services;

use App\Domain\Pemasaran\Domain\Enums\StatusPengirimanWhatsApp;
use App\Domain\Pemasaran\Domain\ValueObjects\PeristiwaWebhookWhatsApp;
use App\Domain\Pemasaran\Domain\ValueObjects\PesanMasukWhatsApp;
use App\Domain\Pemasaran\Domain\ValueObjects\StatusKirimanWhatsApp;
use App\Domain\Platform\Domain\ValueObjects\HasilUjiKoneksi;
use App\Domain\Platform\Domain\ValueObjects\IsianKredensial;
use App\Domain\Platform\Domain\ValueObjects\KredensialPenyedia;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/** Gateway Fonnte (api.fonnte.com): WhatsApp biasa lewat scan QR, tidak resmi. */
final class PenyediaWhatsAppFonnte extends PenyediaWhatsAppTidakResmi
{
    private const URL_DASAR = 'https://api.fonnte.com';

    public function kode(): string
    {
        return 'Fonnte';
    }

    public function nama(): string
    {
        return 'Fonnte';
    }

    protected function ringkasan(): string
    {
        return 'Gateway Fonnte yang mengirim pesan teks dari perangkat WhatsApp yang dipindai di dashboard Fonnte.';
    }

    /** @return list<IsianKredensial> */
    protected function isianPenyedia(): array
    {
        return [
            new IsianKredensial('Token', 'Token perangkat', rahasia: true, petunjuk: 'Token perangkat dari menu Device di dashboard Fonnte.'),
        ];
    }

    protected function kirimTeks(KredensialPenyedia $kredensial, string $nomor, string $teks): string
    {
        $jawaban = $this->panggil($kredensial, 'pengiriman pesan', fn (): Response => $this->http($kredensial)
            ->asForm()
            ->post('/send', ['target' => $nomor, 'message' => $teks]));

        // Fonnte menjawab HTTP 200 juga untuk penolakan; penentunya bidang `status`.
        if ($jawaban->json('status') !== true) {
            throw $this->galat($kredensial, 'pengiriman pesan', $jawaban);
        }

        $id = $jawaban->json('id.0') ?? $jawaban->json('id');

        return is_scalar($id) && (string) $id !== '' ? (string) $id : throw $this->galat(
            $kredensial,
            'pengiriman pesan',
            $jawaban,
            'jawaban tidak memuat id pesan.',
        );
    }

    protected function periksaKoneksi(KredensialPenyedia $kredensial): HasilUjiKoneksi
    {
        $jawaban = $this->panggil($kredensial, 'pemeriksaan perangkat', fn (): Response => $this->http($kredensial)->post('/device'));

        if ($jawaban->json('status') !== true) {
            throw $this->galat($kredensial, 'pemeriksaan perangkat', $jawaban);
        }

        $nama = $this->teksDari($jawaban, 'name');

        if ($this->teksDari($jawaban, 'device_status') !== 'connect') {
            return new HasilUjiKoneksi(false, "Token benar, tetapi perangkat {$nama} tidak tersambung. Pindai ulang kode QR di dashboard Fonnte.");
        }

        return new HasilUjiKoneksi(true, "Tersambung ke perangkat {$nama} ({$this->teksDari($jawaban, 'device')}).");
    }

    protected function pesanGalat(Response $jawaban): string
    {
        return $this->teksDari($jawaban, 'reason') ?: $this->teksDari($jawaban, 'detail');
    }

    /** @param array<mixed> $muatan */
    public function terjemahkanWebhook(array $muatan): PeristiwaWebhookWhatsApp
    {
        $pengirim = $this->teksDalam($muatan, 'sender');
        $isi = $this->teksDalam($muatan, 'message');

        // Pesan masuk membawa `sender` dan `message`; laporan status membawa `id` dan `state`/`status`.
        if ($pengirim !== '' && $isi !== '') {
            return new PeristiwaWebhookWhatsApp(pesanMasuk: [new PesanMasukWhatsApp($pengirim, $isi)]);
        }

        $id = $this->teksDalam($muatan, 'id');
        $status = $this->status($this->teksDalam($muatan, 'state') ?: $this->teksDalam($muatan, 'status'));

        if ($id === '' || $status === null) {
            return new PeristiwaWebhookWhatsApp;
        }

        return new PeristiwaWebhookWhatsApp(status: [new StatusKirimanWhatsApp($id, $status)]);
    }

    private function status(string $nilai): ?StatusPengirimanWhatsApp
    {
        return match (mb_strtolower($nilai)) {
            'sent' => StatusPengirimanWhatsApp::Dikirim,
            'delivered', 'received' => StatusPengirimanWhatsApp::Terkirim,
            'read' => StatusPengirimanWhatsApp::Dibaca,
            'failed', 'invalid', 'expired', 'error' => StatusPengirimanWhatsApp::Gagal,
            default => null,
        };
    }

    private function http(KredensialPenyedia $kredensial): PendingRequest
    {
        return Http::baseUrl(self::URL_DASAR)
            ->withHeaders(['Authorization' => $kredensial->ambil('Token')])
            ->acceptJson()
            ->timeout(self::BATAS_WAKTU_DETIK);
    }
}
