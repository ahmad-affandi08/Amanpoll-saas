<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Services;

use App\Domain\Pemasaran\Domain\Enums\StatusPengirimanWhatsApp;
use App\Domain\Pemasaran\Domain\ValueObjects\PeristiwaWebhookWhatsApp;
use App\Domain\Pemasaran\Domain\ValueObjects\PesanMasukWhatsApp;
use App\Domain\Pemasaran\Domain\ValueObjects\StatusKirimanWhatsApp;
use App\Domain\Platform\Application\Services\PembacaKredensialPenyedia;
use App\Domain\Platform\Domain\ValueObjects\HasilUjiKoneksi;
use App\Domain\Platform\Domain\ValueObjects\IsianKredensial;
use App\Domain\Platform\Domain\ValueObjects\KredensialPenyedia;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Infrastructure\Keamanan\PenjagaUrlKeluar;
use App\Shared\Infrastructure\Keamanan\UrlKeluarDitolak;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;

/** Gateway Wablas: tiap akun memakai domain servernya sendiri; tidak resmi. */
final class PenyediaWhatsAppWablas extends PenyediaWhatsAppTidakResmi
{
    public function __construct(PembacaKredensialPenyedia $pembaca, private readonly PenjagaUrlKeluar $penjaga)
    {
        parent::__construct($pembaca);
    }

    public function kode(): string
    {
        return 'Wablas';
    }

    public function nama(): string
    {
        return 'Wablas';
    }

    protected function ringkasan(): string
    {
        return 'Gateway Wablas yang mengirim pesan teks dari perangkat WhatsApp yang dipindai di server Wablas akun Anda.';
    }

    /** @return list<IsianKredensial> */
    protected function isianPenyedia(): array
    {
        return [
            new IsianKredensial('Domain', 'Domain server', petunjuk: 'Alamat server akun Anda, mis. https://tegal.wablas.com (lihat dashboard Wablas).'),
            new IsianKredensial('Token', 'Token', rahasia: true, petunjuk: 'Token perangkat dari dashboard Wablas.'),
            new IsianKredensial('SecretKey', 'Secret key', rahasia: true, petunjuk: 'Secret key perangkat; Wablas mewajibkannya bersama token.'),
        ];
    }

    protected function kirimTeks(KredensialPenyedia $kredensial, string $nomor, string $teks): string
    {
        $jawaban = $this->panggil($kredensial, 'pengiriman pesan', fn (): Response => $this->http($kredensial)
            ->asForm()
            ->post('/api/send-message', ['phone' => $nomor, 'message' => $teks]));

        if ($jawaban->json('status') !== true) {
            throw $this->galat($kredensial, 'pengiriman pesan', $jawaban);
        }

        $id = $this->teksDari($jawaban, 'data.messages.0.id');

        return $id !== '' ? $id : throw $this->galat($kredensial, 'pengiriman pesan', $jawaban, 'jawaban tidak memuat id pesan.');
    }

    protected function periksaKoneksi(KredensialPenyedia $kredensial): HasilUjiKoneksi
    {
        $jawaban = $this->panggil($kredensial, 'pemeriksaan perangkat', fn (): Response => $this->http($kredensial)->get('/api/device/info'));

        if ($jawaban->json('status') !== true) {
            throw $this->galat($kredensial, 'pemeriksaan perangkat', $jawaban);
        }

        $status = mb_strtolower($this->teksDari($jawaban, 'data.status'));
        $nomor = $this->teksDari($jawaban, 'data.sender');

        if ($status !== 'connected') {
            return new HasilUjiKoneksi(false, "Token benar, tetapi perangkat {$nomor} tidak tersambung. Pindai ulang kode QR di dashboard Wablas.");
        }

        return new HasilUjiKoneksi(true, "Tersambung ke perangkat {$nomor}.");
    }

    protected function pesanGalat(Response $jawaban): string
    {
        return $this->teksDari($jawaban, 'message');
    }

    /** @param array<mixed> $muatan */
    public function terjemahkanWebhook(array $muatan): PeristiwaWebhookWhatsApp
    {
        $id = $this->teksDalam($muatan, 'id');
        $nomor = $this->teksDalam($muatan, 'phone');
        $isi = $this->teksDalam($muatan, 'message');

        // Pesan masuk membawa `message`; laporan status (tracking) membawa `status` tanpa isi.
        if ($isi !== '' && $nomor !== '') {
            $dariGrup = filter_var(data_get($muatan, 'isGroup'), FILTER_VALIDATE_BOOLEAN);
            $dariKita = filter_var(data_get($muatan, 'isFromMe'), FILTER_VALIDATE_BOOLEAN);

            return $dariGrup || $dariKita
                ? new PeristiwaWebhookWhatsApp
                : new PeristiwaWebhookWhatsApp(pesanMasuk: [new PesanMasukWhatsApp($nomor, $isi, $id ?: null)]);
        }

        $status = $this->status($this->teksDalam($muatan, 'status'));

        if ($id === '' || $status === null) {
            return new PeristiwaWebhookWhatsApp;
        }

        $catatan = $this->teksDalam($muatan, 'note');

        return new PeristiwaWebhookWhatsApp(status: [new StatusKirimanWhatsApp($id, $status, $catatan ?: null)]);
    }

    private function status(string $nilai): ?StatusPengirimanWhatsApp
    {
        return match (mb_strtolower($nilai)) {
            'sent' => StatusPengirimanWhatsApp::Dikirim,
            'received', 'delivered' => StatusPengirimanWhatsApp::Terkirim,
            'read' => StatusPengirimanWhatsApp::Dibaca,
            'cancel', 'reject', 'rejected', 'failed' => StatusPengirimanWhatsApp::Gagal,
            default => null,
        };
    }

    private function http(KredensialPenyedia $kredensial): PendingRequest
    {
        return $this->klienTerjaga($this->domain($kredensial))
            ->withHeaders(['Authorization' => $kredensial->ambil('Token').'.'.$kredensial->ambil('SecretKey')])
            ->acceptJson()
            ->timeout(self::BATAS_WAKTU_DETIK);
    }

    /** Domain ditulis operator dengan atau tanpa skema; hanya https yang diterima karena token ikut terkirim. */
    private function domain(KredensialPenyedia $kredensial): string
    {
        $domain = rtrim($kredensial->ambil('Domain'), '/');

        if (! str_contains($domain, '://')) {
            $domain = 'https://'.$domain;
        }

        if (! str_starts_with($domain, 'https://') || ! is_string(parse_url($domain, PHP_URL_HOST))) {
            throw new AturanBisnisDilanggar('Domain server Wablas harus berupa alamat https, mis. https://tegal.wablas.com.');
        }

        return $domain;
    }

    /**
     * Alamat isian admin tetap melewati penjaga jaringan: harus host publik,
     * disematkan ke IP yang diperiksa, dan tanpa mengikuti redirect.
     */
    private function klienTerjaga(string $urlDasar): PendingRequest
    {
        try {
            return $this->penjaga->klienDasar($urlDasar);
        } catch (UrlKeluarDitolak $galat) {
            throw new AturanBisnisDilanggar('Domain server Wablas ditolak penjaga jaringan: '.$galat->getMessage());
        }
    }
}
