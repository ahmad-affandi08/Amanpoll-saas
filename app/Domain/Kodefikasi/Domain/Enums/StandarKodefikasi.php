<?php

declare(strict_types=1);

namespace App\Domain\Kodefikasi\Domain\Enums;

/**
 * Skema kodefikasi barang pemerintah yang dipakai rumah sakit.
 *
 * Rumah sakit vertikal Kemenkes melapor sebagai barang milik negara lewat
 * SIMAK BMN, sedangkan RSUD melapor sebagai barang milik daerah lewat SIMBADA.
 * Keduanya dapat hidup berdampingan pada satu instalasi, jadi kodenya disimpan
 * per standar, bukan satu kolom yang ditumpangi bergantian.
 */
enum StandarKodefikasi: string
{
    case SimakBmn = 'SimakBmn';

    case Simbada = 'Simbada';

    public function label(): string
    {
        return match ($this) {
            self::SimakBmn => 'SIMAK BMN (PMK 29/2010)',
            self::Simbada => 'SIMBADA (Permendagri 108/2016)',
        };
    }

    /**
     * Pola kode yang diterima.
     *
     * SIMAK BMN pasti: sepuluh angka dalam lima ruas, X.XX.XX.XX.XXX --
     * golongan, bidang, kelompok, sub kelompok, sub-sub kelompok.
     *
     * Barang milik daerah sengaja dilonggarkan. Permendagri 108/2016 memakai
     * hierarki tujuh tingkat, tetapi jumlah digit tiap ruasnya tidak berhasil
     * dipastikan dari sumber primer, dan sejumlah pemerintah daerah menerbitkan
     * turunannya sendiri. Menolak kode yang sah hanya karena dugaan format
     * lebih merugikan daripada menerima kode yang bentuknya tidak biasa.
     */
    public function pola(): string
    {
        return match ($this) {
            self::SimakBmn => '/^\d\.\d{2}\.\d{2}\.\d{2}\.\d{3}$/',
            self::Simbada => '/^[\d.]{3,40}$/',
        };
    }

    public function cocok(string $kode): bool
    {
        return preg_match($this->pola(), $kode) === 1;
    }
}
