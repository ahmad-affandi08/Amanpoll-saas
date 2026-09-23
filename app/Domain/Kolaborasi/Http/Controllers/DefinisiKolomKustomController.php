<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Http\Controllers;

use App\Core\Entitas\RegistriEntitas;
use App\Domain\Kolaborasi\Application\Actions\BuatDefinisiKolomKustom;
use App\Domain\Kolaborasi\Application\Actions\HapusDefinisiKolomKustom;
use App\Domain\Kolaborasi\Application\Actions\UbahDefinisiKolomKustom;
use App\Domain\Kolaborasi\Http\Requests\SimpanDefinisiKolomKustomRequest;
use App\Domain\Kolaborasi\Http\Resources\DefinisiKolomKustomResource;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\DefinisiKolomKustom;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Ekspor\EksporDaftar;
use App\Shared\Infrastructure\Ekspor\KolomEkspor;
use App\Shared\Infrastructure\Validasi\AturanWajib;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DefinisiKolomKustomController extends Controller
{
    public function __construct(private readonly RegistriEntitas $registriEntitas) {}

    public function halaman(): Response
    {
        return Inertia::render('KolomKustom/Index', [
            'wajib' => ['kolomKustom' => AturanWajib::untuk(SimpanDefinisiKolomKustomRequest::class)],
            'jenisEntitasTersedia' => $this->registriEntitas->jenisDikenal(),
        ]);
    }

    /**
     * Definisi milik satu jenis entitas, dalam urutan yang sama seperti di layar.
     *
     * @return Builder<DefinisiKolomKustom>
     */
    private function kueriTersaring(string $jenisEntitas): Builder
    {
        return DefinisiKolomKustom::query()
            ->where('JenisEntitas', $jenisEntitas)
            ->orderBy('Urutan')
            ->orderBy('Label')
            // Urutan dan label boleh kembar; tanpa pemutus seri, potongan baca
            // berbasis offset dapat melewatkan satu baris dan menggandakan lainnya.
            ->orderBy('Id');
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $data = $request->validate(['jenisEntitas' => ['required', 'string']]);
        $this->registriEntitas->pastikanBolehKelola($request->user('web'), $data['jenisEntitas']);

        return DefinisiKolomKustomResource::collection($this->kueriTersaring($data['jenisEntitas'])->get());
    }

    /**
     * Layarnya selalu menampilkan satu jenis entitas sekaligus, jadi ekspornya
     * pun terikat jenis yang sedang dipilih; izin kelolanya diperiksa dengan
     * pemeriksaan yang sama seperti daftarnya, bukan pemeriksaan yang lebih longgar.
     */
    public function ekspor(Request $request, EksporDaftar $ekspor): StreamedResponse
    {
        $data = $request->validate(['jenisEntitas' => ['required', 'string']]);
        $this->registriEntitas->pastikanBolehKelola($request->user('web'), $data['jenisEntitas']);

        return $ekspor->unduh(
            $this->kueriTersaring($data['jenisEntitas']),
            [
                KolomEkspor::atribut('Label', 'Label'),
                KolomEkspor::atribut('Kode', 'Kode'),
                KolomEkspor::atribut('Tipe Data', 'TipeData'),
                KolomEkspor::dari('Wajib', fn (DefinisiKolomKustom $d): string => $d->Wajib ? 'Ya' : 'Tidak'),
            ],
            'daftar-kolom-kustom-'.Str::slug($data['jenisEntitas']),
            EksporDaftar::formatDari($request),
        );
    }

    public function store(SimpanDefinisiKolomKustomRequest $request, BuatDefinisiKolomKustom $aksi): RedirectResponse
    {
        $data = $request->validated();
        $this->registriEntitas->pastikanBolehKelola($request->user('web'), $data['JenisEntitas']);

        $aksi->jalankan($data);

        return back()->with('sukses', 'Kolom kustom berhasil dibuat.');
    }

    public function update(SimpanDefinisiKolomKustomRequest $request, DefinisiKolomKustom $definisiKolomKustom, UbahDefinisiKolomKustom $aksi): RedirectResponse
    {
        $this->registriEntitas->pastikanBolehKelola($request->user('web'), $definisiKolomKustom->JenisEntitas);

        $aksi->jalankan($definisiKolomKustom, $request->validated());

        return back()->with('sukses', 'Kolom kustom berhasil diperbarui.');
    }

    public function destroy(DefinisiKolomKustom $definisiKolomKustom, HapusDefinisiKolomKustom $aksi, Request $request): RedirectResponse
    {
        $this->registriEntitas->pastikanBolehKelola($request->user('web'), $definisiKolomKustom->JenisEntitas);

        $aksi->jalankan($definisiKolomKustom);

        return back()->with('sukses', 'Kolom kustom berhasil dihapus.');
    }
}
