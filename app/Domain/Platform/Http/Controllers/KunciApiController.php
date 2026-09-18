<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Controllers;

use App\Domain\Platform\Application\Actions\BuatKunciApi;
use App\Domain\Platform\Application\Actions\CabutKunciApi;
use App\Domain\Platform\Http\Requests\BuatKunciApiRequest;
use App\Domain\Platform\Http\Resources\KunciApiResource;
use App\Domain\Platform\Infrastructure\Persistence\Models\KunciApi;
use App\Http\Controllers\Controller;
use DateTimeImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class KunciApiController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', KunciApi::class);

        $kunciApi = KunciApi::query()->latest('DibuatPada')->paginate(15);

        return Inertia::render('KunciApi/Index', [
            'kunciApi' => KunciApiResource::collection($kunciApi),
        ]);
    }

    public function store(BuatKunciApiRequest $request, BuatKunciApi $aksi): RedirectResponse
    {
        $this->authorize('create', KunciApi::class);

        $data = $request->validated();

        $hasil = $aksi->jalankan(
            $request->user(),
            $data['Nama'],
            $data['Cakupan'] ?? null,
            isset($data['KadaluarsaPada']) ? new DateTimeImmutable($data['KadaluarsaPada']) : null,
            $data['AlamatIpDiizinkan'] ?? null,
        );

        return back()->with([
            'sukses' => 'Kunci API berhasil dibuat.',
            'tokenKunciApi' => $hasil['tokenMentah'],
        ]);
    }

    public function destroy(KunciApi $kunciApi, CabutKunciApi $aksi): RedirectResponse
    {
        $this->authorize('delete', $kunciApi);

        $aksi->jalankan($kunciApi);

        return back()->with('sukses', 'Kunci API berhasil dicabut.');
    }
}
