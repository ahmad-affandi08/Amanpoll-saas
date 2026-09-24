<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Infrastructure\Services;

use App\Domain\Platform\Domain\ValueObjects\KredensialPenyedia;
use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Dasar penyedia email yang dikirim lewat HTTP API, bukan SMTP (PRD 8.23).
 *
 * HTTP API tetap berjalan di shared hosting yang memblokir port SMTP keluar. Semua
 * panggilan memakai facade `Http` Laravel; kegagalan menjadi `TransportException`
 * berpesan Indonesia yang sudah disensor dari kunci API.
 */
abstract class PenyediaEmailHttp extends PenyediaEmailDasar
{
    final public function buatTransport(KredensialPenyedia $kredensial): TransportInterface
    {
        return new TransportEmailHttp(
            $this->kode(),
            fn (Email $surat, Envelope $amplop): ?string => $this->kirim($kredensial, $surat, $amplop),
        );
    }

    /** Mengirim satu email lewat API penyedia dan mengembalikan ID pesannya bila dijawab. */
    abstract protected function kirim(KredensialPenyedia $kredensial, Email $surat, Envelope $amplop): ?string;

    /** Pesan galat di dalam jawaban penyedia; tiap penyedia menaruhnya di tempat berbeda. */
    abstract protected function pesanGalat(Response $jawaban): string;

    /**
     * Menjalankan satu panggilan HTTP dan menerjemahkan kegagalannya menjadi pesan Indonesia.
     *
     * @param  Closure(): Response  $panggilan
     */
    protected function panggil(KredensialPenyedia $kredensial, string $tindakan, Closure $panggilan): Response
    {
        $jawaban = $this->hubungi($tindakan, $panggilan);

        if ($jawaban->failed()) {
            throw $this->galat($kredensial, $tindakan, $jawaban);
        }

        return $jawaban;
    }

    /**
     * Seperti `panggil()`, tetapi jawaban gagal dikembalikan apa adanya untuk diperiksa pemanggil.
     *
     * @param  Closure(): Response  $panggilan
     */
    protected function hubungi(string $tindakan, Closure $panggilan): Response
    {
        try {
            return $panggilan();
        } catch (ConnectionException) {
            // Pesan aslinya memuat URL lengkap; sebagian penyedia menaruh domain akun di sana.
            throw new TransportException("Tidak dapat menghubungi {$this->nama()} saat {$tindakan}. Periksa jaringan server, lalu coba lagi.");
        }
    }

    protected function galat(KredensialPenyedia $kredensial, string $tindakan, Response $jawaban): TransportException
    {
        $rincian = $this->sensor($kredensial, $this->pesanGalat($jawaban));
        $kepala = "{$this->nama()} menolak {$tindakan} (HTTP {$jawaban->status()})";

        return new TransportException($rincian === '' ? "{$kepala}." : "{$kepala}: {$rincian}");
    }

    /**
     * Penerima utama adalah penerima envelope yang bukan tembusan, sehingga alamat
     * yang hanya ada di envelope (mis. dari `Mail::alwaysTo`) tetap terkirim.
     *
     * @return array{kepada: list<Address>, tembusan: list<Address>, tersembunyi: list<Address>}
     */
    protected function penerima(Email $surat, Envelope $amplop): array
    {
        $tembusan = array_values($surat->getCc());
        $tersembunyi = array_values($surat->getBcc());
        $bukanKepada = array_map(
            fn (Address $alamat): string => mb_strtolower($alamat->getAddress()),
            [...$tembusan, ...$tersembunyi],
        );

        $kepada = array_values(array_filter(
            $amplop->getRecipients(),
            fn (Address $alamat): bool => ! in_array(mb_strtolower($alamat->getAddress()), $bukanKepada, true),
        ));

        return ['kepada' => $kepada, 'tembusan' => $tembusan, 'tersembunyi' => $tersembunyi];
    }

    /**
     * Lampiran dengan isi mentahnya; adapter yang butuh base64 menyandikannya sendiri.
     *
     * @return list<array{nama: string, isi: string, jenis: string, inline: bool, idKonten: string|null}>
     */
    protected function lampiran(Email $surat): array
    {
        $hasil = [];

        foreach ($surat->getAttachments() as $bagian) {
            $inline = $bagian->getDisposition() === 'inline';

            $hasil[] = [
                'nama' => $bagian->getFilename() ?? 'lampiran',
                'isi' => $bagian->getBody(),
                'jenis' => $bagian->getContentType(),
                'inline' => $inline,
                'idKonten' => $inline && $bagian->hasContentId() ? $bagian->getContentId() : null,
            ];
        }

        return $hasil;
    }

    protected function html(Email $surat): ?string
    {
        return $this->isiBadan($surat->getHtmlBody());
    }

    protected function teks(Email $surat): ?string
    {
        return $this->isiBadan($surat->getTextBody());
    }

    /** Membaca satu nilai teks dari jawaban JSON tanpa melempar bila bentuknya tak terduga. */
    protected function teksDari(Response $jawaban, string $kunci): string
    {
        $nilai = $jawaban->json($kunci);

        return is_scalar($nilai) ? trim((string) $nilai) : '';
    }

    /** @param  resource|string|null  $badan */
    private function isiBadan(mixed $badan): ?string
    {
        if (is_resource($badan)) {
            $badan = stream_get_contents($badan);
        }

        return is_string($badan) && $badan !== '' ? $badan : null;
    }
}
