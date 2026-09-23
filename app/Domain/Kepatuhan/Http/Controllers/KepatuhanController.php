<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Http\Controllers;

use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Kepatuhan\Application\Actions\KelolaKepatuhanAset;
use App\Domain\Kepatuhan\Application\Actions\KelolaSertifikasiAset;
use App\Domain\Kepatuhan\Application\Actions\KelolaStandarKepatuhan;
use App\Domain\Kepatuhan\Application\Services\LayananKepatuhan;
use App\Domain\Kepatuhan\Domain\Enums\StatusKepatuhanAset;
use App\Domain\Kepatuhan\Domain\Enums\StatusSertifikasiAset;
use App\Domain\Kepatuhan\Http\Requests\CabutSertifikasiRequest;
use App\Domain\Kepatuhan\Http\Requests\SimpanKepatuhanAsetRequest;
use App\Domain\Kepatuhan\Http\Requests\SimpanPersyaratanKepatuhanRequest;
use App\Domain\Kepatuhan\Http\Requests\SimpanSertifikasiAsetRequest;
use App\Domain\Kepatuhan\Http\Requests\SimpanStandarKepatuhanRequest;
use App\Domain\Kepatuhan\Http\Requests\TugaskanStandarRequest;
use App\Domain\Kepatuhan\Http\Resources\KepatuhanAsetResource;
use App\Domain\Kepatuhan\Http\Resources\SertifikasiAsetResource;
use App\Domain\Kepatuhan\Http\Resources\StandarKepatuhanResource;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\KepatuhanAset;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\PersyaratanKepatuhan;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\SertifikasiAset;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\StandarKepatuhan;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Ekspor\EksporDaftar;
use App\Shared\Infrastructure\Ekspor\KolomEkspor;
use App\Shared\Infrastructure\Persistence\BacaRelasi;
use App\Shared\Infrastructure\Validasi\AturanWajib;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class KepatuhanController extends Controller
{
    /**
     * Penyaring daftar kewajiban kepatuhan, dipakai bersama halaman dan ekspornya.
     *
     * @param  array<string, mixed>  $filter
     * @return Builder<KepatuhanAset>
     */
    private function kueriTersaring(array $filter): Builder
    {
        return KepatuhanAset::query()
            ->with(['aset', 'persyaratanKepatuhan.standarKepatuhan', 'diperiksaOleh'])
            ->when($filter['cari'] ?? null, fn ($query, $cari) => $query
                ->whereHas('aset', fn ($sub) => $sub
                    ->where('KodeAset', 'like', "%{$cari}%")
                    ->orWhere('Nama', 'like', "%{$cari}%")))
            ->when($filter['status'] ?? null, fn ($query, $status) => $query->where('Status', $status))
            ->orderByRaw('BerlakuSampai is null, BerlakuSampai asc')
            ->orderBy('Id');
    }

    /** Daftar kewajiban kepatuhan per aset, untuk berkas akreditasi. */
    public function ekspor(Request $request, EksporDaftar $ekspor): StreamedResponse
    {
        $this->authorize('viewAny', StandarKepatuhan::class);

        $filter = $request->validate([
            'cari' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', Rule::enum(StatusKepatuhanAset::class)],
        ]);

        return $ekspor->unduh(
            $this->kueriTersaring($filter),
            [
                KolomEkspor::dari('Kode Aset', fn (KepatuhanAset $k): string => BacaRelasi::teks(BacaRelasi::model($k, 'aset'), 'KodeAset')),
                KolomEkspor::dari('Aset', fn (KepatuhanAset $k): string => BacaRelasi::teks(BacaRelasi::model($k, 'aset'), 'Nama')),
                KolomEkspor::dari('Persyaratan', fn (KepatuhanAset $k): string => BacaRelasi::teks(BacaRelasi::model($k, 'persyaratanKepatuhan'), 'Nama')),
                KolomEkspor::dari('Standar', function (KepatuhanAset $k): string {
                    $persyaratan = BacaRelasi::model($k, 'persyaratanKepatuhan');

                    return $persyaratan === null ? '' : BacaRelasi::teks(BacaRelasi::model($persyaratan, 'standarKepatuhan'), 'Nama');
                }),
                KolomEkspor::atribut('Status', 'Status'),
                KolomEkspor::tanggal('Tanggal Pemeriksaan', 'TanggalPemeriksaan'),
                KolomEkspor::tanggal('Berlaku Sampai', 'BerlakuSampai'),
                KolomEkspor::dari('Diperiksa Oleh', fn (KepatuhanAset $k): string => BacaRelasi::teks(BacaRelasi::model($k, 'diperiksaOleh'), 'Nama')),
                KolomEkspor::atribut('Catatan', 'Catatan'),
            ],
            'kepatuhan-aset',
            EksporDaftar::formatDari($request),
        );
    }

    public function index(Request $request, LayananKepatuhan $layanan): Response
    {
        $this->authorize('viewAny', StandarKepatuhan::class);
        $filter = $request->validate([
            'cari' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', Rule::enum(StatusKepatuhanAset::class)],
        ]);

        $kewajiban = $this->kueriTersaring($filter)->paginate(20)->withQueryString();

        return Inertia::render('Kepatuhan/Index', [
            'wajib' => ['standar' => AturanWajib::untuk(SimpanStandarKepatuhanRequest::class), 'tugaskan' => AturanWajib::untuk(TugaskanStandarRequest::class), 'pemeriksaan' => AturanWajib::untuk(SimpanKepatuhanAsetRequest::class)],
            'kewajiban' => KepatuhanAsetResource::collection($kewajiban),
            'standar' => StandarKepatuhanResource::collection(
                StandarKepatuhan::query()->withCount('persyaratan')->orderBy('Kode')->get()
            ),
            'aset' => Aset::query()->where('Status', StatusAset::Aktif->value)->orderBy('Nama')->get(['Id', 'KodeAset', 'Nama']),
            'ringkasan' => $layanan->ringkasan($request->user('web')->OrganisasiId),
            'filter' => $filter,
        ]);
    }

    public function storeStandar(SimpanStandarKepatuhanRequest $request, KelolaStandarKepatuhan $aksi): RedirectResponse
    {
        $this->authorize('create', StandarKepatuhan::class);
        $standar = $aksi->buat($request->validated());

        return redirect()
            ->route('kepatuhan.standar.show', $standar)
            ->with('sukses', 'Standar kepatuhan dibuat.');
    }

    public function showStandar(StandarKepatuhan $standarKepatuhan): Response
    {
        $this->authorize('view', $standarKepatuhan);
        $standarKepatuhan->load(['persyaratan' => fn ($query) => $query->withCount('kepatuhanAset')->orderBy('Kode')]);

        return Inertia::render('Kepatuhan/Standar', [
            'wajib' => ['persyaratan' => AturanWajib::untuk(SimpanPersyaratanKepatuhanRequest::class)],
            'standar' => new StandarKepatuhanResource($standarKepatuhan),
        ]);
    }

    public function updateStandar(SimpanStandarKepatuhanRequest $request, StandarKepatuhan $standarKepatuhan, KelolaStandarKepatuhan $aksi): RedirectResponse
    {
        $this->authorize('update', $standarKepatuhan);
        $aksi->ubah($standarKepatuhan, $request->validated());

        return back()->with('sukses', 'Standar kepatuhan diperbarui.');
    }

    public function destroyStandar(StandarKepatuhan $standarKepatuhan, KelolaStandarKepatuhan $aksi): RedirectResponse
    {
        $this->authorize('delete', $standarKepatuhan);
        $aksi->hapus($standarKepatuhan);

        return redirect()->route('kepatuhan.index')->with('sukses', 'Standar kepatuhan dihapus.');
    }

    public function storePersyaratan(SimpanPersyaratanKepatuhanRequest $request, StandarKepatuhan $standarKepatuhan, KelolaStandarKepatuhan $aksi): RedirectResponse
    {
        $this->authorize('update', $standarKepatuhan);
        $aksi->tambahPersyaratan($standarKepatuhan, $request->validated());

        return back()->with('sukses', 'Persyaratan ditambahkan.');
    }

    public function destroyPersyaratan(StandarKepatuhan $standarKepatuhan, PersyaratanKepatuhan $persyaratanKepatuhan, KelolaStandarKepatuhan $aksi): RedirectResponse
    {
        $this->authorize('update', $standarKepatuhan);
        $aksi->hapusPersyaratan($standarKepatuhan, $persyaratanKepatuhan);

        return back()->with('sukses', 'Persyaratan dihapus.');
    }

    public function tugaskanStandar(TugaskanStandarRequest $request, KelolaKepatuhanAset $aksi): RedirectResponse
    {
        $this->authorize('create', KepatuhanAset::class);
        $data = $request->validated();
        $aset = Aset::query()->whereKey($data['AsetId'])->firstOrFail();
        $standar = StandarKepatuhan::query()->whereKey($data['StandarKepatuhanId'])->firstOrFail();
        $hasil = $aksi->tugaskanStandar($aset, $standar);

        return back()->with('sukses', "Standar ditugaskan: {$hasil['ditambahkan']} persyaratan baru, {$hasil['dilewati']} sudah ada.");
    }

    public function catatPemeriksaan(SimpanKepatuhanAsetRequest $request, KepatuhanAset $kepatuhanAset, KelolaKepatuhanAset $aksi): RedirectResponse
    {
        $this->authorize('update', $kepatuhanAset);
        $aksi->catatPemeriksaan($kepatuhanAset, $request->validated(), $request->user('web')->Id);

        return back()->with('sukses', 'Hasil pemeriksaan kepatuhan dicatat.');
    }

    public function destroyKepatuhan(KepatuhanAset $kepatuhanAset, KelolaKepatuhanAset $aksi): RedirectResponse
    {
        $this->authorize('delete', $kepatuhanAset);
        $aksi->lepaskan($kepatuhanAset);

        return back()->with('sukses', 'Kewajiban kepatuhan dilepas dari aset.');
    }

    public function indexSertifikasi(Request $request): Response
    {
        $this->authorize('viewAny', SertifikasiAset::class);
        $filter = $request->validate([
            'cari' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', Rule::enum(StatusSertifikasiAset::class)],
        ]);

        $sertifikasi = SertifikasiAset::query()
            ->with('aset')
            ->when($filter['cari'] ?? null, fn ($query, $cari) => $query->where(fn ($sub) => $sub
                ->where('NomorSertifikat', 'like', "%{$cari}%")
                ->orWhere('JenisSertifikasi', 'like', "%{$cari}%")))
            ->when($filter['status'] ?? null, fn ($query, $status) => $query->where('Status', $status))
            ->orderByRaw('BerlakuSampai is null, BerlakuSampai asc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Sertifikasi/Index', [
            'wajib' => ['terbitkan' => AturanWajib::untuk(SimpanSertifikasiAsetRequest::class), 'cabut' => AturanWajib::untuk(CabutSertifikasiRequest::class)],
            'sertifikasi' => SertifikasiAsetResource::collection($sertifikasi),
            'aset' => Aset::query()->where('Status', StatusAset::Aktif->value)->orderBy('Nama')->get(['Id', 'KodeAset', 'Nama']),
            'filter' => $filter,
        ]);
    }

    public function storeSertifikasi(SimpanSertifikasiAsetRequest $request, KelolaSertifikasiAset $aksi): RedirectResponse
    {
        $this->authorize('create', SertifikasiAset::class);
        $data = $request->validated();
        $aset = Aset::query()->whereKey($data['AsetId'])->firstOrFail();
        $aksi->terbitkan($aset, $data);

        return back()->with('sukses', 'Sertifikat aset dicatat.');
    }

    public function updateSertifikasi(SimpanSertifikasiAsetRequest $request, SertifikasiAset $sertifikasiAset, KelolaSertifikasiAset $aksi): RedirectResponse
    {
        $this->authorize('update', $sertifikasiAset);
        $aksi->ubah($sertifikasiAset, $request->validated());

        return back()->with('sukses', 'Sertifikat aset diperbarui.');
    }

    public function cabutSertifikasi(CabutSertifikasiRequest $request, SertifikasiAset $sertifikasiAset, KelolaSertifikasiAset $aksi): RedirectResponse
    {
        $this->authorize('update', $sertifikasiAset);
        $aksi->cabut($sertifikasiAset, $request->validated()['Alasan']);

        return back()->with('sukses', 'Sertifikat dicabut.');
    }
}
