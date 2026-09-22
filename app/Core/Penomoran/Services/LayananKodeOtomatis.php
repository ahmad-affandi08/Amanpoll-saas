<?php

declare(strict_types=1);

namespace App\Core\Penomoran\Services;

use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Penerbit kode data induk: awalan entitas ditambah nomor urut per organisasi.
 *
 * Berbeda dari LayananNomorDokumen yang menolak jalan sebelum polanya diatur,
 * penghitung di sini dibuat sendiri saat pertama dipakai supaya organisasi baru
 * tidak perlu menyiapkan apa pun lebih dulu.
 */
final class LayananKodeOtomatis
{
    /** Penanda lingkup untuk entitas milik platform yang tidak bertenant. */
    public const PLATFORM = '00000000000000000000000000';

    private const MAKS_LOMPATAN = 100;

    private const MAKS_PERCOBAAN = 5;

    /**
     * @param  callable(string): bool  $sudahDipakai  memeriksa kode calon pada tabel sasaran
     */
    public function berikutnya(
        string $entitas,
        string $awalan,
        ?string $organisasiId,
        callable $sudahDipakai,
    ): string {
        $lingkup = $organisasiId ?? self::PLATFORM;

        return DB::transaction(function () use ($entitas, $awalan, $lingkup, $sudahDipakai): string {
            $penghitung = $this->penghitungTerkunci($entitas, $lingkup);
            $nomor = $penghitung['Terakhir'];

            /*
             * Kode yang ditulis tangan bisa menempati nomor yang belum terpakai
             * penghitung, jadi calon yang bentrok dilewati dan bukan digagalkan.
             */
            for ($lompatan = 0; $lompatan < self::MAKS_LOMPATAN; $lompatan++) {
                $nomor++;
                $kode = sprintf('%s-%04d', $awalan, $nomor);

                if ($sudahDipakai($kode)) {
                    continue;
                }

                DB::table('UrutanKode')
                    ->where('Id', $penghitung['Id'])
                    ->update(['Terakhir' => $nomor, 'DiperbaruiPada' => now()]);

                return $kode;
            }

            throw new AturanBisnisDilanggar(
                "Tidak menemukan kode {$awalan} yang belum terpakai setelah ".self::MAKS_LOMPATAN.' percobaan.',
            );
        }, self::MAKS_PERCOBAAN);
    }

    /** @return array{Id: string, Terakhir: int} */
    private function penghitungTerkunci(string $entitas, string $lingkup): array
    {
        $ambil = fn (): ?object => DB::table('UrutanKode')
            ->where('OrganisasiId', $lingkup)
            ->where('Entitas', $entitas)
            ->lockForUpdate()
            ->first();

        $baris = $ambil();

        if ($baris !== null) {
            return $this->sebagaiPenghitung($baris);
        }

        DB::table('UrutanKode')->insertOrIgnore([
            'Id' => (string) Str::ulid(),
            'OrganisasiId' => $lingkup,
            'Entitas' => $entitas,
            'Terakhir' => 0,
            'DibuatPada' => now(),
            'DiperbaruiPada' => now(),
        ]);

        $baris = $ambil();

        if ($baris === null) {
            throw new AturanBisnisDilanggar("Penghitung kode untuk '{$entitas}' gagal dibuat.");
        }

        return $this->sebagaiPenghitung($baris);
    }

    /** @return array{Id: string, Terakhir: int} */
    private function sebagaiPenghitung(object $baris): array
    {
        $kolom = (array) $baris;

        return ['Id' => (string) $kolom['Id'], 'Terakhir' => (int) $kolom['Terakhir']];
    }
}
