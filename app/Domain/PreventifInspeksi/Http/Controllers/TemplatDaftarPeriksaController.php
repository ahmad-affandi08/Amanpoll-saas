<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Http\Controllers;

use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\ModelAset;
use App\Domain\PreventifInspeksi\Application\Actions\KelolaTemplatDaftarPeriksa;
use App\Domain\PreventifInspeksi\Http\Requests\SimpanButirTemplatDaftarPeriksaRequest;
use App\Domain\PreventifInspeksi\Http\Requests\SimpanTemplatDaftarPeriksaRequest;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\TemplatDaftarPeriksa;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Ekspor\EksporDaftar;
use App\Shared\Infrastructure\Ekspor\KolomEkspor;
use App\Shared\Infrastructure\Persistence\BacaRelasi;
use App\Shared\Infrastructure\Validasi\AturanWajib;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class TemplatDaftarPeriksaController extends Controller
{
    public function __construct(
        private readonly KelolaTemplatDaftarPeriksa $kelolaTemplat,
    ) {}

    /**
     * Daftar templat daftar periksa, dipakai bersama halaman dan ekspornya.
     *
     * @return Builder<TemplatDaftarPeriksa>
     */
    private function kueriTersaring(): Builder
    {
        return TemplatDaftarPeriksa::query()
            ->with(['kategoriAset', 'modelAset'])
            ->withCount('butir')
            ->orderBy('Nama')
            ->orderBy('Id');
    }

    /** Daftar templat beserta versinya, untuk ditinjau saat audit lembar periksa. */
    public function ekspor(Request $request, EksporDaftar $ekspor): StreamedResponse
    {
        $this->authorize('viewAny', TemplatDaftarPeriksa::class);

        return $ekspor->unduh(
            $this->kueriTersaring(),
            [
                KolomEkspor::atribut('Kode', 'Kode'),
                KolomEkspor::atribut('Nama', 'Nama'),
                KolomEkspor::atribut('Jenis', 'Jenis'),
                KolomEkspor::dari('Kategori Aset', fn (TemplatDaftarPeriksa $templat): string => BacaRelasi::teks(BacaRelasi::model($templat, 'kategoriAset'), 'Nama')),
                KolomEkspor::dari('Model Aset', fn (TemplatDaftarPeriksa $templat): string => BacaRelasi::teks(BacaRelasi::model($templat, 'modelAset'), 'Nama')),
                KolomEkspor::atribut('Versi', 'VersiTemplat'),
                KolomEkspor::atribut('Jumlah Butir', 'butir_count'),
                KolomEkspor::dari('Aktif', fn (TemplatDaftarPeriksa $templat): string => $templat->Aktif ? 'Ya' : 'Tidak'),
            ],
            'daftar-templat-daftar-periksa',
            EksporDaftar::formatDari($request),
        );
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', TemplatDaftarPeriksa::class);

        $daftarTemplat = $this->kueriTersaring()->get();

        return Inertia::render('DaftarPeriksa/Templat/Index', [
            'wajib' => ['templat' => AturanWajib::untuk(SimpanTemplatDaftarPeriksaRequest::class)],
            'templat' => $daftarTemplat,
            'kategoriAset' => KategoriAset::query()->orderBy('Nama')->get(['Id', 'Nama']),
            'modelAset' => ModelAset::query()->orderBy('Nama')->get(['Id', 'Nama', 'KategoriAsetId']),
        ]);
    }

    public function store(SimpanTemplatDaftarPeriksaRequest $request): RedirectResponse
    {
        $this->authorize('create', TemplatDaftarPeriksa::class);

        $templat = $this->kelolaTemplat->buat(
            $request->validated(),
            $request->user('web')->Id
        );

        return redirect()->route('preventifInspeksi.templat-daftar-periksa.show', $templat->Id)
            ->with('sukses', 'Templat daftar periksa berhasil dibuat.');
    }

    public function show(TemplatDaftarPeriksa $templatDaftarPeriksa): Response
    {
        $this->authorize('view', $templatDaftarPeriksa);

        $templatDaftarPeriksa->load([
            'kategoriAset',
            'modelAset',
            'butir' => fn ($q) => $q->orderBy('Urutan'),
        ]);

        return Inertia::render('DaftarPeriksa/Templat/Show', [
            'wajib' => ['butir' => AturanWajib::untuk(SimpanButirTemplatDaftarPeriksaRequest::class)],
            'templat' => $templatDaftarPeriksa,
            'kategoriAset' => KategoriAset::query()->orderBy('Nama')->get(['Id', 'Nama']),
            'modelAset' => ModelAset::query()->orderBy('Nama')->get(['Id', 'Nama', 'KategoriAsetId']),
        ]);
    }

    public function update(SimpanTemplatDaftarPeriksaRequest $request, TemplatDaftarPeriksa $templatDaftarPeriksa): RedirectResponse
    {
        $this->authorize('update', $templatDaftarPeriksa);

        $this->kelolaTemplat->perbarui(
            $templatDaftarPeriksa,
            $request->validated(),
            $request->user('web')->Id
        );

        return back()->with('sukses', 'Templat daftar periksa berhasil diperbarui.');
    }

    public function buatVersiBaru(Request $request, TemplatDaftarPeriksa $templatDaftarPeriksa): RedirectResponse
    {
        $this->authorize('create', TemplatDaftarPeriksa::class);

        $versiBaru = $this->kelolaTemplat->buatVersiBaru(
            $templatDaftarPeriksa,
            $request->user('web')->Id
        );

        return redirect()->route('preventifInspeksi.templat-daftar-periksa.show', $versiBaru->Id)
            ->with('sukses', "Versi baru ({$versiBaru->Kode}) berhasil dibuat.");
    }

    public function destroy(Request $request, TemplatDaftarPeriksa $templatDaftarPeriksa): RedirectResponse
    {
        $this->authorize('delete', $templatDaftarPeriksa);

        $this->kelolaTemplat->hapus(
            $templatDaftarPeriksa,
            $request->user('web')->Id
        );

        return redirect()->route('preventifInspeksi.templat-daftar-periksa.index')
            ->with('sukses', 'Templat daftar periksa berhasil dihapus.');
    }
}
