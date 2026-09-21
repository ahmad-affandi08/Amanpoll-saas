<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Application\Actions;

use App\Domain\Persediaan\Domain\Enums\JenisMutasiStok;
use App\Domain\Persediaan\Domain\Enums\StatusMutasiStok;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\DetailMutasiStok;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\MutasiStok;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

final class TambahDetailMutasiStok
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function jalankan(MutasiStok $mutasiStok, array $data): DetailMutasiStok
    {
        if ($mutasiStok->Status !== StatusMutasiStok::Draft->value) {
            throw new AturanBisnisDilanggar('Hanya mutasi berstatus draft yang bisa ditambah detail.');
        }

        if ($mutasiStok->Jenis !== JenisMutasiStok::Adjustment->value && (float) $data['Jumlah'] <= 0) {
            throw new AturanBisnisDilanggar('Jumlah harus lebih besar dari nol.');
        }

        if ($mutasiStok->Jenis === JenisMutasiStok::Adjustment->value && (float) $data['Jumlah'] === 0.0) {
            throw new AturanBisnisDilanggar('Jumlah penyesuaian tidak boleh nol.');
        }

        $data['OrganisasiId'] = $mutasiStok->OrganisasiId;
        $data['MutasiStokId'] = $mutasiStok->Id;

        return DetailMutasiStok::create($data);
    }
}
