<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Http\Controllers;

use App\Core\Entitas\RegistriEntitas;
use App\Domain\Aset\Application\Services\GaleriFotoAset;
use App\Domain\Kolaborasi\Application\Actions\HapusBerkas;
use App\Domain\Kolaborasi\Application\Actions\LampirkanBerkas;
use App\Domain\Kolaborasi\Application\Actions\UnggahBerkas;
use App\Domain\Kolaborasi\Application\Services\PenyimpanBerkas;
use App\Domain\Kolaborasi\Http\Requests\SimpanBerkasRequest;
use App\Domain\Kolaborasi\Http\Resources\BerkasResource;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Berkas;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\LampiranEntitas;
use App\Http\Controllers\Controller;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class BerkasController extends Controller
{
    public function __construct(private readonly RegistriEntitas $registriEntitas) {}

    public function store(SimpanBerkasRequest $request, UnggahBerkas $aksi, LampirkanBerkas $aksiLampirkan): RedirectResponse
    {
        $this->authorize('create', Berkas::class);

        $data = $request->validated();

        if (isset($data['JenisEntitas'], $data['EntitasId'])) {
            $this->registriEntitas->pastikanBolehKelolaRekaman($request->user('web'), $data['JenisEntitas'], $data['EntitasId']);
        } elseif (isset($data['JenisEntitas'])) {
            $this->registriEntitas->pastikanBolehKelola($request->user('web'), $data['JenisEntitas']);
        }

        $berkas = $aksi->jalankan($request->file('Berkas'), $request->user('web')->Id);

        if (isset($data['JenisEntitas'], $data['EntitasId'])) {
            $aksiLampirkan->jalankan(
                $data['JenisEntitas'],
                $data['EntitasId'],
                $berkas->Id,
                $data['Kategori'] ?? null,
                $data['Keterangan'] ?? null,
                $request->user('web')->Id,
            );
        }

        return back()->with('berkasDiunggah', (new BerkasResource($berkas))->resolve());
    }

    public function unduh(Berkas $berkas, PenyimpanBerkas $penyimpan): StreamedResponse
    {
        $this->authorize('view', $berkas);

        return $penyimpan->responsUnduh($berkas);
    }

    /** Thumbnail untuk daftar dan galeri; izinnya sama dengan unduh. */
    public function thumbnail(Berkas $berkas, PenyimpanBerkas $penyimpan): StreamedResponse
    {
        $this->authorize('view', $berkas);

        return $penyimpan->responsThumbnail($berkas);
    }

    public function destroy(Berkas $berkas, HapusBerkas $aksi): RedirectResponse
    {
        $this->authorize('delete', $berkas);

        // Foto galeri aset dihapus lewat galerinya, supaya foto utama ikut berpindah (PRD 8.4).
        if (LampiranEntitas::query()->where('BerkasId', $berkas->Id)->where('Kategori', GaleriFotoAset::KATEGORI)->exists()) {
            throw new AturanBisnisDilanggar('Berkas ini foto galeri aset. Hapus lewat galeri foto di halaman aset.');
        }

        $aksi->jalankan($berkas);

        return back()->with('sukses', 'Berkas berhasil dihapus.');
    }
}
