<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Resources;

use App\Domain\Platform\Application\Services\LayananNomorDokumen;
use App\Domain\Platform\Infrastructure\Persistence\Models\NomorDokumen;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class NomorDokumenResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var NomorDokumen $nomorDokumen */
        $nomorDokumen = $this->resource;

        return [
            'Id' => $nomorDokumen->Id,
            'JenisDokumen' => $nomorDokumen->JenisDokumen,
            'Awalan' => $nomorDokumen->Awalan,
            'FormatNomor' => $nomorDokumen->FormatNomor,
            'NomorTerakhir' => $nomorDokumen->NomorTerakhir,
            'ResetPeriode' => $nomorDokumen->ResetPeriode,
            'PeriodeAktif' => $nomorDokumen->PeriodeAktif,
            'Pratinjau' => app(LayananNomorDokumen::class)->pratinjau($nomorDokumen->OrganisasiId, $nomorDokumen->JenisDokumen),
        ];
    }
}
