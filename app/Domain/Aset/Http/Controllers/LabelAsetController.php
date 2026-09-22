<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Controllers;

use App\Domain\Aset\Http\Requests\CetakLabelAsetRequest;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Http\Controllers\Controller;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Infrastructure\Qr\PembuatQrAset;
use Illuminate\Database\Eloquent\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Lembar label aset siap cetak.
 *
 * Aset sudah lama menyimpan KodeQr dan sistem sudah bisa membacanya lewat
 * AsetPindaiController, tetapi kodenya tidak pernah dirender -- jadi alur
 * pindai PRD 10 buntu: tidak ada cara menempelkan QR-nya ke aset fisik.
 */
final class LabelAsetController extends Controller
{
    public function __invoke(CetakLabelAsetRequest $request, PembuatQrAset $pembuat): Response
    {
        $this->authorize('viewAny', Aset::class);

        /** @var array{id: list<string>} $sah */
        $sah = $request->validated();

        // ScopeOrganisasi sudah menyaring per tenant; whereIn di atasnya hanya
        // memilih dari yang memang milik organisasi ini.
        $aset = Aset::query()
            ->whereIn('Id', $sah['id'])
            ->with(['kategoriAset', 'lokasi'])
            ->orderBy('KodeAset')
            ->get();

        if ($aset->isEmpty()) {
            throw new AturanBisnisDilanggar('Tidak ada aset yang dapat dicetak labelnya.');
        }

        // Izin dicek per aset, bukan sekali di depan: viewAny saja tidak cukup
        // bila kebijakan nanti membatasi aset tertentu.
        $aset->each(fn (Aset $satu) => $this->authorize('view', $satu));

        $svgPerKode = $this->svgPerKode($pembuat, $aset);
        $label = [];

        foreach ($aset as $satu) {
            $label[] = [
                'Id' => $satu->Id,
                'KodeAset' => $satu->KodeAset,
                'Nama' => $satu->Nama,
                'Kategori' => $satu->kategoriAset?->Nama,
                'Lokasi' => $satu->lokasi?->Nama,
                'NomorSeri' => $satu->NomorSeri,
                // Aset lama dapat belum punya KodeQr; labelnya tetap dicetak
                // dengan kode asetnya saja supaya barisnya tidak hilang diam-diam.
                'Svg' => $satu->KodeQr === null ? null : ($svgPerKode[$satu->KodeQr] ?? null),
            ];
        }

        return Inertia::render('Aset/Label', ['label' => $label]);
    }

    /**
     * @param  Collection<int, Aset>  $aset
     * @return array<string, string>
     */
    private function svgPerKode(PembuatQrAset $pembuat, Collection $aset): array
    {
        $kode = [];

        foreach ($aset as $satu) {
            if ($satu->KodeQr !== null && $satu->KodeQr !== '') {
                $kode[] = $satu->KodeQr;
            }
        }

        if ($kode === []) {
            return [];
        }

        $svgPerKode = [];

        foreach ($pembuat->untuk($kode, CetakLabelAsetRequest::MAKS_LABEL) as $satu) {
            $svgPerKode[$satu['Kode']] = $satu['Svg'];
        }

        return $svgPerKode;
    }
}
