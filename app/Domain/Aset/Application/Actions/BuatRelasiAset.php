<?php

declare(strict_types=1);

namespace App\Domain\Aset\Application\Actions;

use App\Domain\Aset\Domain\Enums\JenisRelasiAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\RelasiAset;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Support\Facades\DB;

final class BuatRelasiAset
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function jalankan(array $data): RelasiAset
    {
        if ($data['AsetIndukId'] === $data['AsetAnakId']) {
            throw new AturanBisnisDilanggar('Aset tidak boleh direlasikan dengan dirinya sendiri.');
        }

        if (($data['JenisRelasi'] ?? JenisRelasiAset::Komponen->value) === JenisRelasiAset::Komponen->value) {
            $this->pastikanTidakSirkular($data['AsetIndukId'], $data['AsetAnakId']);
        }

        return RelasiAset::create($data);
    }

    /** Menolak relasi Komponen yang akan membuat siklus. */
    private function pastikanTidakSirkular(string $asetIndukId, string $asetAnakId): void
    {
        $antrian = [$asetAnakId];
        $dikunjungi = [];

        while ($antrian !== []) {
            $sekarang = array_shift($antrian);

            if ($sekarang === $asetIndukId) {
                throw new AturanBisnisDilanggar('Relasi ini akan membuat hierarki komponen melingkar.');
            }

            if (isset($dikunjungi[$sekarang])) {
                continue;
            }
            $dikunjungi[$sekarang] = true;

            $anak = DB::table('RelasiAset')
                ->where('AsetIndukId', $sekarang)
                ->where('JenisRelasi', JenisRelasiAset::Komponen->value)
                ->pluck('AsetAnakId');

            foreach ($anak as $a) {
                $antrian[] = $a;
            }
        }
    }
}
