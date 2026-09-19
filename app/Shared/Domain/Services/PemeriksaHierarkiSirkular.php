<?php

declare(strict_types=1);

namespace App\Shared\Domain\Services;

use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Support\Facades\DB;

/**
 * Menolak penetapan induk yang akan membuat hierarki melingkar pada tabel
 * self-referencing mana pun (mis. UnitOrganisasi.IndukId, Lokasi.IndukId).
 * Ditulis generik lewat DB::table supaya dipakai lintas domain tanpa
 * bergantung pada model Eloquent tertentu.
 */
final class PemeriksaHierarkiSirkular
{
    public static function pastikanTidakSirkular(string $tabel, string $kolomInduk, string $id, ?string $indukId): void
    {
        if ($indukId === null) {
            return;
        }

        if ($indukId === $id) {
            throw new AturanBisnisDilanggar('Tidak boleh menjadikan diri sendiri sebagai induk.');
        }

        $idPenelusuran = $indukId;
        $dikunjungi = [];

        while ($idPenelusuran !== null) {
            if ($idPenelusuran === $id) {
                throw new AturanBisnisDilanggar('Perubahan ini akan membuat hierarki melingkar.');
            }

            if (isset($dikunjungi[$idPenelusuran])) {
                return;
            }
            $dikunjungi[$idPenelusuran] = true;

            $baris = DB::table($tabel)->where('Id', $idPenelusuran)->first([$kolomInduk]);
            $idPenelusuran = $baris?->{$kolomInduk};
        }
    }
}
