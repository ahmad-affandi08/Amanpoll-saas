<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Infrastructure\Services;

use App\Domain\Notifikasi\Application\Services\PemberitahuLayananPengirim;
use App\Domain\Notifikasi\Application\Services\PemilihPengirimNotifikasi;
use App\Domain\Notifikasi\Domain\Contracts\PenyediaEmail;
use App\Domain\Notifikasi\Domain\ValueObjects\PengirimOrganisasi;
use App\Domain\Platform\Application\Actions\CatatKesehatanPenyediaOrganisasi;
use App\Domain\Platform\Application\Services\KatalogPenyediaLayanan;
use App\Domain\Platform\Application\Services\PembacaKredensialPenyedia;
use App\Domain\Platform\Domain\Contracts\DeskripsiPenyediaLayanan;
use App\Domain\Platform\Domain\Enums\KategoriPenyediaLayanan;
use App\Domain\Platform\Domain\ValueObjects\KredensialPenyedia;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Contracts\Container\Container;
use Illuminate\Mail\MailManager;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Header\MailboxListHeader;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\RawMessage;

/**
 * Transport mailer `amanpoll`: meneruskan setiap email ke penyedia email yang tepat (PRD 8.23).
 *
 * Email notifikasi membawa header organisasi. Bila organisasi itu memasang email sendiri
 * dan paketnya mengizinkan, surat berangkat lewat penyedia organisasi; bila penyedia itu
 * gagal, surat tetap berangkat lewat penyedia platform supaya kabar penting tidak hilang.
 * Lewat penyedia platform, nama pengirim menjadi "<organisasi> via <nama pengirim>" dan
 * balasan diarahkan ke email organisasi. Surat tanpa header (reset kata sandi, pemasaran)
 * selalu lewat penyedia platform.
 *
 * Penyedia dan kredensialnya dibaca ulang pada setiap kiriman, karena transport ini
 * disimpan MailManager selama proses hidup (termasuk `queue:work`). Tanpa penyedia
 * platform aktif, email diteruskan ke mailer cadangan dari konfigurasi.
 *
 * Alamat pengirim bawaan aplikasi (`mail.from.address`) diganti dengan alamat pengirim
 * milik penyedia, karena penyedia hanya mau mengirim dari domain yang sudah diverifikasi.
 * Pengirim yang disetel sendiri oleh pemanggil dibiarkan.
 */
final class TransportEmailAmanpoll implements TransportInterface
{
    /** Penanda organisasi pemilik surat; dibuang sebelum surat meninggalkan aplikasi. */
    public const HEADER_ORGANISASI = 'X-Amanpoll-Organisasi';

    public function __construct(
        private readonly PembacaKredensialPenyedia $pembaca,
        private readonly KatalogPenyediaLayanan $katalog,
        private readonly MailManager $pengelolaSurat,
        private readonly string $mailerCadangan,
        private readonly Container $container,
    ) {}

    public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
    {
        [$message, $organisasiId] = $this->lepasPenandaOrganisasi($message);

        if ($organisasiId !== null) {
            $terkirim = $this->kirimLewatOrganisasi($message, $envelope, $organisasiId);

            if ($terkirim !== null) {
                return $terkirim;
            }
        }

        return $this->kirimLewatPlatform($message, $envelope, $organisasiId);
    }

    public function __toString(): string
    {
        return 'amanpoll';
    }

    /**
     * Mengembalikan null bila organisasi tidak memakai email sendiri atau penyedianya gagal;
     * pemanggil lalu mengirim lewat platform.
     */
    private function kirimLewatOrganisasi(RawMessage $pesan, ?Envelope $amplop, string $organisasiId): ?SentMessage
    {
        $pengirim = $this->container->make(PemilihPengirimNotifikasi::class)->emailOrganisasi($organisasiId);

        if ($pengirim === null) {
            return null;
        }

        [$pesan, $amplop] = $this->denganPengirim($pesan, $amplop, $pengirim->kredensial);
        $kesehatan = $this->container->make(CatatKesehatanPenyediaOrganisasi::class);

        try {
            $terkirim = $pengirim->penyedia->buatTransport($pengirim->kredensial)->send($pesan, $amplop);
        } catch (AturanBisnisDilanggar|TransportExceptionInterface $galat) {
            $this->catatGagal($pengirim, $organisasiId, $galat->getMessage());

            return null;
        }

        $kesehatan->berhasil($pengirim->penyediaId);

        return $terkirim;
    }

    private function kirimLewatPlatform(RawMessage $pesan, ?Envelope $amplop, ?string $organisasiId): ?SentMessage
    {
        $kode = $this->pembaca->kodeUtama(KategoriPenyediaLayanan::Email);

        if ($kode === null) {
            return $this->cadangan()->send($pesan, $amplop);
        }

        $penyedia = $this->katalog->untuk(KategoriPenyediaLayanan::Email, $kode);
        $kredensial = $this->pembaca->untuk(KategoriPenyediaLayanan::Email, $kode);

        if (! $penyedia instanceof PenyediaEmail || $kredensial === null) {
            Log::warning('Penyedia email aktif tidak dikenal; email diteruskan ke mailer cadangan.', ['Kode' => $kode]);

            return $this->cadangan()->send($pesan, $amplop);
        }

        [$pesan, $amplop] = $this->denganPengirim($pesan, $amplop, $kredensial, $organisasiId);

        return $penyedia->buatTransport($kredensial)->send($pesan, $amplop);
    }

    /** @param  PengirimOrganisasi<DeskripsiPenyediaLayanan&PenyediaEmail>  $pengirim */
    private function catatGagal(PengirimOrganisasi $pengirim, string $organisasiId, string $galat): void
    {
        $pesan = $pengirim->kredensial->sensor($galat, $pengirim->penyedia->isian());
        $baruBermasalah = $this->container->make(CatatKesehatanPenyediaOrganisasi::class)->gagal($pengirim->penyediaId, $pesan);

        Log::warning('Email organisasi gagal dikirim; dialihkan ke penyedia platform.', [
            'OrganisasiId' => $organisasiId,
            'Kode' => $pengirim->kredensial->kode,
            'Galat' => $pesan,
        ]);

        if ($baruBermasalah) {
            $this->container->make(PemberitahuLayananPengirim::class)->penyediaBermasalah($organisasiId, 'Email', $pesan);
        }
    }

    private function cadangan(): TransportInterface
    {
        // Menunjuk dirinya sendiri akan berputar tanpa akhir; log setidaknya menyimpan isinya.
        $nama = $this->mailerCadangan === '' || $this->mailerCadangan === 'amanpoll' ? 'log' : $this->mailerCadangan;

        return $this->pengelolaSurat->mailer($nama)->getSymfonyTransport();
    }

    /** @return array{0: RawMessage, 1: string|null} */
    private function lepasPenandaOrganisasi(RawMessage $pesan): array
    {
        if (! $pesan instanceof Message || ! $pesan->getHeaders()->has(self::HEADER_ORGANISASI)) {
            return [$pesan, null];
        }

        $pesan = clone $pesan;
        $organisasiId = trim((string) $pesan->getHeaders()->get(self::HEADER_ORGANISASI)?->getBodyAsString());
        $pesan->getHeaders()->remove(self::HEADER_ORGANISASI);

        return [$pesan, $organisasiId === '' ? null : $organisasiId];
    }

    /**
     * Mengganti From bawaan aplikasi dengan pengirim penyedia, lalu membuat ulang envelope.
     *
     * Envelope bawaan Laravel membaca pengirimnya dari objek pesan yang lama, jadi
     * tanpa dibuat ulang penyedia tetap menerima alamat bawaan sebagai pengirim.
     * Organisasi yang disebut berarti surat notifikasinya berangkat lewat penyedia platform.
     *
     * @return array{0: RawMessage, 1: Envelope|null}
     */
    private function denganPengirim(RawMessage $pesan, ?Envelope $amplop, KredensialPenyedia $kredensial, ?string $organisasiId = null): array
    {
        if (! $pesan instanceof Message || ! $this->memakaiPengirimBawaan($pesan)) {
            return [$pesan, $amplop];
        }

        $nama = $kredensial->ambilAtau(PenyediaEmail::ISIAN_NAMA_PENGIRIM);
        $organisasi = $organisasiId === null ? null : Organisasi::query()->whereKey($organisasiId)->first(['Id', 'Nama', 'Email']);

        $pesan = clone $pesan;
        $pesan->getHeaders()->remove('From');
        $pesan->getHeaders()->addMailboxListHeader('From', [new Address(
            $kredensial->ambil(PenyediaEmail::ISIAN_ALAMAT_PENGIRIM),
            $organisasi === null ? $nama : $this->namaAtasNama((string) $organisasi->Nama, $nama),
        )]);

        if ($organisasi !== null && ! $pesan->getHeaders()->has('Reply-To')) {
            $balasKe = trim((string) $organisasi->Email);

            if ($balasKe !== '' && filter_var($balasKe, FILTER_VALIDATE_EMAIL) !== false) {
                $pesan->getHeaders()->addMailboxListHeader('Reply-To', [new Address($balasKe, (string) $organisasi->Nama)]);
            }
        }

        if ($amplop === null) {
            return [$pesan, null];
        }

        return [$pesan, new Envelope(Envelope::create($pesan)->getSender(), $amplop->getRecipients())];
    }

    /** "RS Sehat via Amanpoll": penerima mengenali rumah sakitnya, penyedia tetap mengirim dari domainnya. */
    private function namaAtasNama(string $organisasi, string $pengirim): string
    {
        $organisasi = trim($organisasi);

        if ($organisasi === '') {
            return $pengirim;
        }

        return $pengirim === '' ? $organisasi : "{$organisasi} via {$pengirim}";
    }

    private function memakaiPengirimBawaan(Message $pesan): bool
    {
        $bawaan = mb_strtolower(trim((string) config('mail.from.address')));
        $header = $pesan->getHeaders()->get('From');

        if ($bawaan === '' || ! $header instanceof MailboxListHeader) {
            return false;
        }

        $dari = $header->getAddresses();

        return count($dari) === 1 && mb_strtolower($dari[0]->getAddress()) === $bawaan;
    }
}
