<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Http\Controllers;

use App\Core\Entitas\RegistriEntitas;
use App\Domain\Aset\Application\Services\GaleriFotoAset;
use App\Domain\Kolaborasi\Application\Actions\LampirkanBerkas;
use App\Domain\Kolaborasi\Application\Actions\LepaskanLampiran;
use App\Domain\Kolaborasi\Http\Requests\SimpanLampiranEntitasRequest;
use App\Domain\Kolaborasi\Http\Resources\LampiranEntitasResource;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\LampiranEntitas;
use App\Http\Controllers\Controller;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Infrastructure\Persistence\BatasDaftar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class LampiranEntitasController extends Controller
{
    public function __construct(private readonly RegistriEntitas $registriEntitas) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $data = $request->validate([
            'jenisEntitas' => ['required', 'string'],
            'entitasId' => ['required', 'string'],
        ]);

        $this->registriEntitas->cariEntitas($data['jenisEntitas'], $data['entitasId']);
        $this->registriEntitas->pastikanBolehKelolaRekaman($request->user('web'), $data['jenisEntitas'], $data['entitasId']);

        $lampiran = LampiranEntitas::query()
            ->with('berkas')
            ->where('JenisEntitas', $data['jenisEntitas'])
            ->where('EntitasId', $data['entitasId'])
            // Foto galeri aset tampil di galerinya sendiri, bukan di daftar lampiran umum (PRD 8.4).
            ->where(fn ($kueri) => $kueri->whereNull('Kategori')->orWhere('Kategori', '!=', GaleriFotoAset::KATEGORI))
            ->latest('DibuatPada')
            ->limit(BatasDaftar::MAKS)
            ->get();

        return LampiranEntitasResource::collection($lampiran);
    }

    public function store(SimpanLampiranEntitasRequest $request, LampirkanBerkas $aksi): RedirectResponse
    {
        $data = $request->validated();
        $this->registriEntitas->pastikanBolehKelolaRekaman($request->user('web'), $data['JenisEntitas'], $data['EntitasId']);

        $aksi->jalankan(
            $data['JenisEntitas'],
            $data['EntitasId'],
            $data['BerkasId'],
            $data['Kategori'] ?? null,
            $data['Keterangan'] ?? null,
            $request->user('web')->Id,
        );

        return back()->with('sukses', 'Lampiran berhasil ditambahkan.');
    }

    public function destroy(LampiranEntitas $lampiranEntitas, LepaskanLampiran $aksi, Request $request): RedirectResponse
    {
        $this->registriEntitas->pastikanBolehKelola($request->user('web'), $lampiranEntitas->JenisEntitas);

        if ($lampiranEntitas->Kategori === GaleriFotoAset::KATEGORI) {
            throw new AturanBisnisDilanggar('Lampiran ini foto galeri aset. Hapus lewat galeri foto di halaman aset.');
        }

        $aksi->jalankan($lampiranEntitas);

        return back()->with('sukses', 'Lampiran berhasil dihapus.');
    }
}
