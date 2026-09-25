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
 * dan tanpa menyentuh penyedia yang sedang dipakai aplikasi. Dipakai konsol platform
 * dan pengaturan Email & WhatsApp organisasi.
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
                ->text($this->teks($penyedia, $kredensial))
                ->html('<p>'.e($this->teks($penyedia, $kredensial)).'</p>');

            $penyedia->buatTransport($kredensial)->send($surat);
        } catch (AturanBisnisDilanggar $galat) {
            return new HasilUjiKoneksi(false, $galat->getMessage());
        } catch (TransportExceptionInterface $galat) {
            return new HasilUjiKoneksi(
                false,
                "Email uji gagal dikirim lewat {$penyedia->nama()}: ".$kredensial->sensor($galat->getMessage(), $penyedia->isian()),
            );
        }

        return new HasilUjiKoneksi(true, "Email uji dikirim ke {$alamatTujuan} lewat {$penyedia->nama()}. Periksa kotak masuk dan folder spam.");
    }

    private function teks(DeskripsiPenyediaLayanan $penyedia, KredensialPenyedia $kredensial): string
    {
        return "Email ini dikirim dari {$kredensial->tempat} di Amanpoll untuk memastikan pengiriman lewat {$penyedia->nama()} berjalan.";
    }
}
