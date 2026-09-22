<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObjects;

use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/** Nilai uang disimpan sebagai integer satuan terkecil (bukan float) agar bebas galat pembulatan biner. */
final readonly class Uang
{
    private function __construct(
        private int $nilaiMinor,
        private int $skala,
        private string $mataUang,
    ) {}

    public static function nol(int $skala = 2, string $mataUang = 'IDR'): self
    {
        return new self(0, $skala, $mataUang);
    }

    public static function dariMinor(int $nilaiMinor, int $skala = 2, string $mataUang = 'IDR'): self
    {
        return new self($nilaiMinor, $skala, $mataUang);
    }

    public static function dariString(string $nilai, int $skala = 2, string $mataUang = 'IDR'): self
    {
        $nilai = trim($nilai);
        $negatif = str_starts_with($nilai, '-');
        if ($negatif) {
            $nilai = substr($nilai, 1);
        }

        [$bagianBulat, $bagianPecahanMentah] = array_pad(explode('.', $nilai, 2), 2, '0');
        [$bagianPecahan, $bawaan] = self::bulatkanPecahan($bagianPecahanMentah, $skala);

        $minor = ((int) $bagianBulat) * (10 ** $skala) + (int) $bagianPecahan + $bawaan;

        return new self($negatif ? -$minor : $minor, $skala, $mataUang);
    }

    /**
     * @return array{0: string, 1: int} bagian pecahan sepanjang skala, dan bawaan pembulatan (0 atau 1)
     */
    private static function bulatkanPecahan(string $bagianPecahanMentah, int $skala): array
    {
        $bagianPecahanMentah = str_pad($bagianPecahanMentah, $skala + 1, '0');
        $dipotong = substr($bagianPecahanMentah, 0, $skala);
        $digitPembulatan = (int) substr($bagianPecahanMentah, $skala, 1);

        return [$dipotong, $digitPembulatan >= 5 ? 1 : 0];
    }

    public function tambah(self $lain): self
    {
        $this->pastikanSebanding($lain);

        return new self($this->nilaiMinor + $lain->nilaiMinor, $this->skala, $this->mataUang);
    }

    public function kurang(self $lain): self
    {
        $this->pastikanSebanding($lain);

        return new self($this->nilaiMinor - $lain->nilaiMinor, $this->skala, $this->mataUang);
    }

    public function kali(int $faktor): self
    {
        return new self($this->nilaiMinor * $faktor, $this->skala, $this->mataUang);
    }

    public function samaDengan(self $lain): bool
    {
        return $this->skala === $lain->skala
            && $this->mataUang === $lain->mataUang
            && $this->nilaiMinor === $lain->nilaiMinor;
    }

    public function lebihBesarDari(self $lain): bool
    {
        $this->pastikanSebanding($lain);

        return $this->nilaiMinor > $lain->nilaiMinor;
    }

    private function pastikanSebanding(self $lain): void
    {
        if ($this->mataUang !== $lain->mataUang || $this->skala !== $lain->skala) {
            throw new AturanBisnisDilanggar('Operasi Uang membutuhkan mata uang dan skala yang sama.');
        }
    }

    public function nilaiMinor(): int
    {
        return $this->nilaiMinor;
    }

    public function mataUang(): string
    {
        return $this->mataUang;
    }

    public function keString(): string
    {
        $negatif = $this->nilaiMinor < 0;
        $absolut = (string) abs($this->nilaiMinor);
        $absolut = str_pad($absolut, $this->skala + 1, '0', STR_PAD_LEFT);

        $bulat = substr($absolut, 0, -$this->skala);
        $pecahan = substr($absolut, -$this->skala);

        return ($negatif ? '-' : '').$bulat.'.'.$pecahan;
    }

    /** Hanya untuk tampilan (mis. */
    public function keFloatTampilan(): float
    {
        return (float) $this->keString();
    }
}
