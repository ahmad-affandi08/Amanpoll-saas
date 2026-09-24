<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Application\Actions;

use App\Domain\Notifikasi\Domain\Contracts\PenyediaEmail;
use App\Domain\Platform\Domain\Contracts\DeskripsiPenyediaLayanan;
use App\Domain\Platform\Domain\ValueObjects\HasilUjiKoneksi;
use App\Domain\Platform\Domain\ValueObjects\KredensialPenyedia;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Mengirim satu email uji lewat penyedia email tertentu dengan kredensial tersimpannya (PRD 8.23).
 *
 * Tidak lewat mailer `amanpoll`, supaya penyedia bisa dicoba sebelum diaktifkan
 * dan tanpa menyentuh penyedia yang sedang dipakai aplikasi.
 */
final class KirimEmailUjiPenyedia
{
    public function jalankan(
        DeskripsiPenyediaLayanan&PenyediaEmail $penyedia,
        KredensialPenyedia $kredensial,
        string $alamatTujuan,
    ): HasilUjiKoneksi {
        try {
            $surat = (new Email)
                ->from(new Address(
                    $kredensial->ambil(PenyediaEmail::ISIAN_ALAMAT_PENGIRIM),
                    $kredensial->ambilAtau(PenyediaEmail::ISIAN_NAMA_PENGIRIM),
                ))
                ->to($alamatTujuan)
                ->subject('Email uji Amanpoll')
                ->text($this->teks($penyedia))
                ->html('<p>'.e($this->teks($penyedia)).'</p>');

            $penyedia->buatTransport($kredensial)->send($surat);
        } catch (AturanBisnisDilanggar $galat) {
            return new HasilUjiKoneksi(false, $galat->getMessage());
        } catch (TransportExceptionInterface $galat) {
            return new HasilUjiKoneksi(
                false,
                "Email uji gagal dikirim lewat {$penyedia->nama()}: ".$this->sensor($penyedia, $kredensial, $galat->getMessage()),
            );
        }

        return new HasilUjiKoneksi(true, "Email uji dikirim ke {$alamatTujuan} lewat {$penyedia->nama()}. Periksa kotak masuk dan folder spam.");
    }

    private function teks(DeskripsiPenyediaLayanan $penyedia): string
    {
        return "Email ini dikirim dari konsol platform Amanpoll untuk memastikan pengiriman lewat {$penyedia->nama()} berjalan.";
    }

    /** Pesan galat SMTP atau API bisa mengutip kredensial; setiap nilai rahasia dibuang. */
    private function sensor(DeskripsiPenyediaLayanan $penyedia, KredensialPenyedia $kredensial, string $teks): string
    {
        foreach ($penyedia->isian() as $isian) {
            $nilai = $isian->rahasia ? $kredensial->ambilAtau($isian->kunci) : '';

            if ($nilai !== '') {
                $teks = str_replace($nilai, '***', $teks);
            }
        }

        return mb_substr(trim($teks), 0, 300);
    }
}
