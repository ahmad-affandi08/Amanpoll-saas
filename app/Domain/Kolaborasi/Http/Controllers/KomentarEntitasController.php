<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Http\Controllers;

use App\Core\Entitas\RegistriEntitas;
use App\Domain\Kolaborasi\Application\Actions\HapusKomentar;
use App\Domain\Kolaborasi\Application\Actions\TambahKomentar;
use App\Domain\Kolaborasi\Application\Actions\UbahKomentar;
use App\Domain\Kolaborasi\Http\Requests\SimpanKomentarEntitasRequest;
use App\Domain\Kolaborasi\Http\Requests\UbahKomentarEntitasRequest;
use App\Domain\Kolaborasi\Http\Resources\KomentarEntitasResource;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\KomentarEntitas;
use App\Http\Controllers\Controller;
use App\Shared\Domain\Exceptions\AksesDitolak;
use App\Shared\Infrastructure\Persistence\BatasDaftar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class KomentarEntitasController extends Controller
{
    public function __construct(private readonly RegistriEntitas $registriEntitas) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $data = $request->validate([
            'jenisEntitas' => ['required', 'string'],
            'entitasId' => ['required', 'string'],
        ]);

        $this->registriEntitas->cariEntitas($data['jenisEntitas'], $data['entitasId']);
        $this->registriEntitas->pastikanBolehKelola($request->user('web'), $data['jenisEntitas']);

        $komentar = KomentarEntitas::query()
            ->with('dibuatOleh')
            ->where('JenisEntitas', $data['jenisEntitas'])
            ->where('EntitasId', $data['entitasId'])
            ->oldest('DibuatPada')
            ->limit(BatasDaftar::MAKS)
            ->get();

        return KomentarEntitasResource::collection($komentar);
    }

    public function store(SimpanKomentarEntitasRequest $request, TambahKomentar $aksi): RedirectResponse
    {
        $data = $request->validated();
        $this->registriEntitas->pastikanBolehKelola($request->user('web'), $data['JenisEntitas']);

        $aksi->jalankan(
            $data['JenisEntitas'],
            $data['EntitasId'],
            $data['Isi'],
            $data['IndukKomentarId'] ?? null,
            $request->user('web')->Id,
        );

        return back()->with('sukses', 'Komentar berhasil ditambahkan.');
    }

    public function update(UbahKomentarEntitasRequest $request, KomentarEntitas $komentarEntitas, UbahKomentar $aksi): RedirectResponse
    {
        if ($komentarEntitas->DibuatOleh !== $request->user('web')->Id) {
            throw new AksesDitolak('Hanya penulis komentar yang dapat mengubahnya.');
        }

        $aksi->jalankan($komentarEntitas, $request->validated()['Isi']);

        return back()->with('sukses', 'Komentar berhasil diperbarui.');
    }

    public function destroy(KomentarEntitas $komentarEntitas, HapusKomentar $aksi, Request $request): RedirectResponse
    {
        $penulis = $komentarEntitas->DibuatOleh === $request->user('web')->Id;
        $pengelola = $this->registriEntitas->bolehKelola($request->user('web'), $komentarEntitas->JenisEntitas);

        if (! $penulis && ! $pengelola) {
            throw new AksesDitolak('Anda tidak memiliki izin untuk menghapus komentar ini.');
        }

        $aksi->jalankan($komentarEntitas);

        return back()->with('sukses', 'Komentar berhasil dihapus.');
    }
}
