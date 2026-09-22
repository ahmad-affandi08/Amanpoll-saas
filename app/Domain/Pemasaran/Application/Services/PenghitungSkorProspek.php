<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Pemasaran\Domain\KatalogKonfigurasiPemasaran;
use App\Domain\Pemasaran\Domain\KatalogPeristiwaSkor;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\EventPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Prospek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\SkorProspek;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use Carbon\CarbonImmutable;

/**
 * Menghitung skor prospek dari peristiwanya (MARKETING.md 5.4).
 *
 * Bobotnya dibaca dari tabel AturanSkorProspek, tidak pernah ditulis di kode
 * program. Perhitungannya disusun ulang dari nol setiap kali dijalankan, bukan
 * ditambahkan ke angka yang sudah ada — skor yang diakumulasi akan ikut menyimpan
 * setiap kesalahan sebelumnya dan tidak pernah dapat dikoreksi.
 *
 * Satu peristiwa hanya dihitung sekali per prospek. Membuka halaman harga
 * sepuluh kali menunjukkan minat, tetapi tidak sepuluh kali lipat minat.
 */
final class PenghitungSkorProspek
{
    public function __construct(
        private readonly LayananKonfigurasiPemasaran $konfigurasi,
        private readonly LayananAturanSkorProspek $aturanSkor,
        private readonly TransaksiDatabase $transaksi,
    ) {}

    public function hitungUlang(Prospek $prospek): int
    {
        return $this->transaksi->jalankan(function () use ($prospek): int {
            $aturan = $this->aturanSkor->bobotBerlaku();
            $petaId = $this->aturanSkor->petaId();

            $sumbangan = [
                ...$this->dariPeristiwa($prospek, $aturan),
                ...$this->dariKeaktifan($prospek, $aturan),
            ];

            SkorProspek::query()->where('ProspekId', $prospek->Id)->delete();

            $total = 0;
            foreach ($sumbangan as $peristiwa => $bobot) {
                SkorProspek::create([
                    'ProspekId' => $prospek->Id,
                    // Menunjuk aturan yang menghasilkannya, supaya pertanyaan
                    // "kenapa angkanya segini" dapat dijawab sampai ke barisnya.
                    'AturanSkorProspekId' => $petaId[$peristiwa] ?? null,
                    'Peristiwa' => $peristiwa,
                    'Bobot' => $bobot,
                    'DihitungPada' => now(),
                ]);
                $total += $bobot;
            }

            $prospek->Skor = $total;
            $prospek->save();

            return $total;
        });
    }

    public function qualified(Prospek $prospek): bool
    {
        return $prospek->Skor >= $this->konfigurasi->angka(
            KatalogKonfigurasiPemasaran::SKOR_AMBANG_QUALIFIED,
        );
    }

    /**
     * @param  array<string, int>  $aturan
     * @return array<string, int>
     */
    private function dariPeristiwa(Prospek $prospek, array $aturan): array
    {
        if ($prospek->PengenalPengunjung === null) {
            return [];
        }

        /** @var list<string> $jenis */
        $jenis = EventPemasaran::query()
            ->where('PengenalPengunjung', $prospek->PengenalPengunjung)
            ->distinct()
            ->pluck('Jenis')
            ->all();

        $sumbangan = [];
        foreach ($jenis as $satu) {
            if (array_key_exists($satu, $aturan)) {
                $sumbangan[$satu] = $aturan[$satu];
            }
        }

        return $sumbangan;
    }

    /**
     * @param  array<string, int>  $aturan
     * @return array<string, int>
     */
    private function dariKeaktifan(Prospek $prospek, array $aturan): array
    {
        $terakhir = $prospek->AktivitasTerakhirPada;

        if ($terakhir === null) {
            return [];
        }

        $hari = CarbonImmutable::now()->diffInDays($terakhir, absolute: true);
        $sumbangan = [];

        foreach (KatalogPeristiwaSkor::turunan() as $peristiwa) {
            if (! array_key_exists($peristiwa, $aturan)) {
                continue;
            }

            $berlaku = $peristiwa === KatalogPeristiwaSkor::AKTIF_TIGA_HARI ? $hari <= 3 : $hari >= 14;

            if ($berlaku) {
                $sumbangan[$peristiwa] = $aturan[$peristiwa];
            }
        }

        return $sumbangan;
    }
}
