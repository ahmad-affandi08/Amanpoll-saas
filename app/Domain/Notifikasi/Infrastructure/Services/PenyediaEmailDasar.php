<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Infrastructure\Services;

use App\Domain\Notifikasi\Domain\Contracts\PenyediaEmail;
use App\Domain\Platform\Domain\Contracts\DapatDiujiKoneksi;
use App\Domain\Platform\Domain\Contracts\DeskripsiPenyediaLayanan;
use App\Domain\Platform\Domain\Enums\KategoriPenyediaLayanan;
use App\Domain\Platform\Domain\ValueObjects\HasilUjiKoneksi;
use App\Domain\Platform\Domain\ValueObjects\IsianKredensial;
use App\Domain\Platform\Domain\ValueObjects\KredensialPenyedia;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Dasar adapter penyedia email yang diatur di konsol platform (PRD 8.23).
 *
 * Setiap penyedia meminta alamat dan nama pengirim, karena penyedia hanya mau
 * mengirim dari alamat atau domain yang sudah diverifikasi di akunnya. Nilai isian
 * rahasia disensor dari setiap pesan galat sebelum sampai ke layar atau log.
 */
abstract class PenyediaEmailDasar implements DapatDiujiKoneksi, DeskripsiPenyediaLayanan, PenyediaEmail
{
    protected const BATAS_WAKTU_DETIK = 15;

    final public function kategori(): KategoriPenyediaLayanan
    {
        return KategoriPenyediaLayanan::Email;
    }

    public function resmi(): bool
    {
        return true;
    }

    public function mendukungModeUji(): bool
    {
        return false;
    }

    /** @return list<IsianKredensial> */
    final public function isian(): array
    {
        return [
            ...$this->isianPenyedia(),
            new IsianKredensial(
                PenyediaEmail::ISIAN_ALAMAT_PENGIRIM,
                'Alamat pengirim',
                petunjuk: 'Alamat From, mis. no-reply@domainanda.id. Domainnya harus sudah diverifikasi di penyedia ini.',
            ),
            new IsianKredensial(
                PenyediaEmail::ISIAN_NAMA_PENGIRIM,
                'Nama pengirim',
                petunjuk: 'Nama yang tampil di kotak masuk penerima.',
                bawaan: (string) config('app.name', 'Amanpoll'),
            ),
        ];
    }

    final public function ujiKoneksi(KredensialPenyedia $kredensial): HasilUjiKoneksi
    {
        try {
            return $this->periksaKoneksi($kredensial);
        } catch (AturanBisnisDilanggar|TransportExceptionInterface $galat) {
            return new HasilUjiKoneksi(false, $this->sensor($kredensial, $galat->getMessage()));
        }
    }

    /** @return list<IsianKredensial> */
    abstract protected function isianPenyedia(): array;

    abstract protected function periksaKoneksi(KredensialPenyedia $kredensial): HasilUjiKoneksi;

    /** Membuang setiap nilai isian rahasia dari teks, lalu memendekkannya. */
    public function sensor(KredensialPenyedia $kredensial, string $teks): string
    {
        foreach ($this->isian() as $isian) {
            $nilai = $isian->rahasia ? $kredensial->ambilAtau($isian->kunci) : '';

            if ($nilai !== '') {
                $teks = str_replace($nilai, '***', $teks);
            }
        }

        return mb_substr(trim($teks), 0, 300);
    }
}
