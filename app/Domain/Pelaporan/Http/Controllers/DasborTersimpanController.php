<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Http\Controllers;

use App\Domain\Pelaporan\Application\Actions\KelolaDasborTersimpan;
use App\Domain\Pelaporan\Application\Services\LayananDasbor;
use App\Domain\Pelaporan\Application\Services\LayananMetrik;
use App\Domain\Pelaporan\Domain\Enums\BentukKomponen;
use App\Domain\Pelaporan\Domain\KatalogKpi;
use App\Domain\Pelaporan\Http\Requests\SimpanDasborTersimpanRequest;
use App\Domain\Pelaporan\Infrastructure\Persistence\Models\DasborTersimpan;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Validasi\AturanWajib;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Dasbor kustom milik pengguna (21.04). */
final class DasborTersimpanController extends Controller
{
    public function index(Request $request, LayananDasbor $layananDasbor, LayananMetrik $layananMetrik): Response
    {
        $this->authorize('viewAny', DasborTersimpan::class);

        $pengguna = $request->user('web');

        return Inertia::render('DashboardKustom/Index', [
            'wajib' => ['dasbor' => AturanWajib::untuk(SimpanDasborTersimpanRequest::class)],
            'dasbor' => $layananDasbor->dasborUntuk($pengguna)
                ->map(fn (DasborTersimpan $dasbor): array => [
                    ...$layananDasbor->dariTersimpan($dasbor),
                    'Id' => $dasbor->Id,
                    'Bawaan' => $dasbor->Bawaan,
                    'Milik' => $dasbor->PemilikId === $pengguna->Id,
                ])
                ->values()
                ->all(),
            'preset' => $layananDasbor->preset($pengguna),
            'katalogKpi' => array_map(function (array $kpi): array {
                return [
                    ...$kpi,
                    'Bentuk' => array_map(
                        fn (BentukKomponen $bentuk): array => [
                            'Nilai' => $bentuk->value,
                            'Label' => $bentuk->label(),
                        ],
                        BentukKomponen::untukKpi(KatalogKpi::ambil((string) $kpi['Kunci'])),
                    ),
                ];
            }, $layananMetrik->katalogUntuk($pengguna)),
            'batasKomponen' => KelolaDasborTersimpan::BATAS_KOMPONEN,
        ]);
    }

    public function store(SimpanDasborTersimpanRequest $request, KelolaDasborTersimpan $aksi): RedirectResponse
    {
        $this->authorize('create', DasborTersimpan::class);
        $aksi->simpan($request->user('web'), $request->validated());

        return back()->with('sukses', 'Dasbor kustom dibuat.');
    }

    public function update(
        SimpanDasborTersimpanRequest $request,
        DasborTersimpan $dasborTersimpan,
        KelolaDasborTersimpan $aksi,
    ): RedirectResponse {
        $this->authorize('update', $dasborTersimpan);
        $aksi->simpan($request->user('web'), $request->validated(), $dasborTersimpan);

        return back()->with('sukses', 'Dasbor kustom diperbarui.');
    }

    public function destroy(DasborTersimpan $dasborTersimpan, KelolaDasborTersimpan $aksi): RedirectResponse
    {
        $this->authorize('delete', $dasborTersimpan);
        $aksi->hapus($dasborTersimpan);

        return back()->with('sukses', 'Dasbor kustom dihapus.');
    }
}
