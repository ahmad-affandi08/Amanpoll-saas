<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Contracts;

use App\Domain\Pemasaran\Domain\Enums\ModulDemo;

/** Satu dataset yang dapat dibangun ulang di dalam tenant demo (MARKETING.md 11). */
interface DatasetDemo
{
    public function kode(): string;

    public function nama(): string;

    /** @return list<ModulDemo> */
    public function modul(): array;

    /**
     * Tabel yang isinya dimiliki dataset ini, urut dari anak ke induk.
     *
     * Reset menghapus baris tabel-tabel ini milik tenant demo, jadi daftarnya
     * sekaligus menjadi batas tegas sejauh mana reset boleh menghapus.
     *
     * @return list<string>
     */
    public function tabel(): array;

    /** Mengisi tenant demo dari nol; pemanggilnya sudah memastikan tenant itu benar-benar tenant demo. */
    public function bangun(string $organisasiId): void;
}
