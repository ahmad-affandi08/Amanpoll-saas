<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence;

use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use stdClass;

/**
 * Penghapusan massal per potongan untuk perintah retensi (FASE 45).
 *
 * Satu `DELETE ... WHERE DibuatPada < ?` atas jutaan baris mengunci tabel dan
 * menulis undo log sebesar seluruh baris itu dalam satu transaksi; di shared
 * hosting prosesnya dibunuh di tengah jalan dan seluruhnya digulung balik.
 * Di sini tiap potongan adalah pernyataan tersendiri (`DELETE ... LIMIT n`),
 * dengan jeda kecil di antaranya supaya permintaan pengguna tetap mendapat
 * giliran, dan berhenti begitu batas waktu habis -- sisanya diteruskan jalan
 * terjadwal berikutnya, karena syaratnya sama.
 *
 * Kueri yang diberikan berupa query builder dasar (`DB::table(...)`), tanpa
 * global scope: retensi adalah operasi sistem lintas organisasi.
 */
final class PenghapusBertahap
{
    private float $tenggat;

    public function __construct(
        private readonly int $potongan,
        private readonly int $jedaMilidetik,
        float $batasDetik,
    ) {
        $this->tenggat = microtime(true) + max(0.0, $batasDetik);
    }

    public static function dariKonfigurasi(): self
    {
        return new self(
            max(1, (int) config('amanpoll.retensi.potongan', 5000)),
            max(0, (int) config('amanpoll.retensi.jeda_milidetik', 100)),
            (float) config('amanpoll.retensi.batas_detik', 240),
        );
    }

    /** Apakah batas waktu total sudah habis; aturan berikutnya sebaiknya tidak dimulai. */
    public function waktuHabis(): bool
    {
        return microtime(true) >= $this->tenggat;
    }

    /**
     * Menghapus seluruh baris yang cocok, satu potongan per pernyataan.
     *
     * @return array{Dihapus: int, Tuntas: bool} Tuntas false bila berhenti karena batas waktu
     */
    public function hapus(Builder $kueri): array
    {
        $dihapus = 0;

        while (true) {
            $jumlah = (clone $kueri)->limit($this->potongan)->delete();
            $dihapus += $jumlah;

            if ($jumlah < $this->potongan) {
                return ['Dihapus' => $dihapus, 'Tuntas' => true];
            }

            if ($this->waktuHabis()) {
                return ['Dihapus' => $dihapus, 'Tuntas' => false];
            }

            $this->jeda();
        }
    }

    /**
     * Seperti hapus(), tetapi tiap potongan diserahkan dulu ke `$sebelumHapus`
     * (mis. untuk diarsipkan) dan hanya baris potongan itu -- menurut kolom
     * kuncinya -- yang kemudian dihapus. Bila `$sebelumHapus` melempar galat,
     * potongannya tidak dihapus.
     *
     * @param  Closure(Collection<int, stdClass>): void  $sebelumHapus
     * @return array{Dihapus: int, Tuntas: bool}
     */
    public function arsipkanLaluHapus(Builder $kueri, Closure $sebelumHapus, string $kolomKunci = 'Id'): array
    {
        $dihapus = 0;

        while (true) {
            $baris = (clone $kueri)->orderBy($kolomKunci)->limit($this->potongan)->get();

            if ($baris->isEmpty()) {
                return ['Dihapus' => $dihapus, 'Tuntas' => true];
            }

            $sebelumHapus($baris);

            $dihapus += (clone $kueri)->whereIn($kolomKunci, $baris->pluck($kolomKunci)->all())->delete();

            if ($baris->count() < $this->potongan) {
                return ['Dihapus' => $dihapus, 'Tuntas' => true];
            }

            if ($this->waktuHabis()) {
                return ['Dihapus' => $dihapus, 'Tuntas' => false];
            }

            $this->jeda();
        }
    }

    private function jeda(): void
    {
        if ($this->jedaMilidetik > 0) {
            usleep($this->jedaMilidetik * 1000);
        }
    }
}
