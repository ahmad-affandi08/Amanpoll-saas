<?php

declare(strict_types=1);

namespace Tests\Dukungan;

use App\Domain\Platform\Domain\Contracts\DapatDiujiKoneksi;
use App\Domain\Platform\Domain\Contracts\DeskripsiPenyediaLayanan;
use App\Domain\Platform\Domain\Enums\KategoriPenyediaLayanan;
use App\Domain\Platform\Domain\ValueObjects\HasilUjiKoneksi;
use App\Domain\Platform\Domain\ValueObjects\IsianKredensial;
use App\Domain\Platform\Domain\ValueObjects\KredensialPenyedia;
use RuntimeException;

/** Penyedia rekaan untuk menguji konsol penyedia layanan tanpa adapter sungguhan (PRD 8.23). */
final class PenyediaLayananUji implements DapatDiujiKoneksi, DeskripsiPenyediaLayanan
{
    public ?KredensialPenyedia $kredensialDiuji = null;

    public bool $gagalTakTerduga = false;

    public function __construct(
        private readonly KategoriPenyediaLayanan $kategori,
        private readonly string $kode,
        private readonly bool $resmi = true,
    ) {}

    public function kategori(): KategoriPenyediaLayanan
    {
        return $this->kategori;
    }

    public function kode(): string
    {
        return $this->kode;
    }

    public function nama(): string
    {
        return 'Penyedia '.$this->kode;
    }

    public function keterangan(): string
    {
        return 'Penyedia rekaan untuk test.';
    }

    public function resmi(): bool
    {
        return $this->resmi;
    }

    public function isian(): array
    {
        return [
            new IsianKredensial('IdPedagang', 'ID pedagang'),
            new IsianKredensial('KunciRahasia', 'Kunci rahasia', rahasia: true),
            new IsianKredensial('Kanal', 'Kanal', wajib: false, pilihan: ['VA', 'QRIS'], bawaan: 'VA'),
        ];
    }

    public function mendukungModeUji(): bool
    {
        return true;
    }

    public function ujiKoneksi(KredensialPenyedia $kredensial): HasilUjiKoneksi
    {
        if ($this->gagalTakTerduga) {
            throw new RuntimeException('https://api.contoh.test/?key='.$kredensial->ambil('KunciRahasia'));
        }

        $this->kredensialDiuji = $kredensial;

        return new HasilUjiKoneksi(true, 'Terhubung sebagai '.$kredensial->ambil('IdPedagang').'.');
    }
}
