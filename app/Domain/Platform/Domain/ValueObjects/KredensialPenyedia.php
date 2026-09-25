<?php

declare(strict_types=1);

namespace App\Domain\Platform\Domain\ValueObjects;

use App\Domain\Platform\Domain\Enums\KategoriPenyediaLayanan;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/**
 * Kredensial satu penyedia yang sudah didekripsi, hanya hidup di memori (PRD 8.23).
 *
 * Jangan diserialisasi, dicatat ke log, atau dikirim ke peramban.
 */
final readonly class KredensialPenyedia
{
    public const TEMPAT_PLATFORM = 'konsol platform';

    public const TEMPAT_ORGANISASI = 'pengaturan Email & WhatsApp organisasi';

    /** @param  array<string, string>  $nilai */
    public function __construct(
        public KategoriPenyediaLayanan $kategori,
        public string $kode,
        public bool $modeUji,
        private array $nilai,
        /** Tempat kredensial ini diatur, dipakai pesan galat supaya pembacanya tahu harus membuka apa. */
        public string $tempat = self::TEMPAT_PLATFORM,
    ) {}

    public function ambil(string $kunci): string
    {
        $isi = $this->nilai[$kunci] ?? '';

        if ($isi === '') {
            throw new AturanBisnisDilanggar("Isian {$kunci} untuk penyedia {$this->kode} belum diatur di {$this->tempat}.");
        }

        return $isi;
    }

    public function ambilAtau(string $kunci, string $bawaan = ''): string
    {
        $isi = $this->nilai[$kunci] ?? '';

        return $isi === '' ? $bawaan : $isi;
    }

    /**
     * Membuang setiap nilai isian rahasia dari teks, lalu memendekkannya.
     *
     * Pesan galat SMTP dan API kerap mengutip token atau kata sandi, sedangkan pesan itu
     * berakhir di layar, log, dan kolom galat.
     *
     * @param  list<IsianKredensial>  $isian
     */
    public function sensor(string $teks, array $isian, int $panjang = 300): string
    {
        foreach ($isian as $satu) {
            $nilai = $satu->rahasia ? ($this->nilai[$satu->kunci] ?? '') : '';

            if ($nilai !== '') {
                $teks = str_replace($nilai, '***', $teks);
            }
        }

        return mb_substr(trim($teks), 0, $panjang);
    }

    /** @return array<string, never> */
    public function __debugInfo(): array
    {
        return [];
    }
}
