<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Controllers;

use App\Core\Audit\LayananAudit;
use App\Domain\Aset\Application\Actions\BuatAset;
use App\Domain\Aset\Application\Actions\HapusAset;
use App\Domain\Aset\Application\Actions\UbahAset;
use App\Domain\Aset\Http\Requests\CetakLabelAsetRequest;
use App\Domain\Aset\Http\Requests\SimpanAsetRequest;
use App\Domain\Aset\Http\Resources\AsetResource;
use App\Domain\Aset\Http\Resources\KategoriAsetResource;
use App\Domain\Aset\Http\Resources\ModelAsetResource;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\ModelAset;
use App\Domain\Penyedia\Http\Resources\PenyediaResource;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;
use App\Domain\Platform\Http\Resources\LokasiResource;
use App\Domain\Platform\Http\Resources\UnitOrganisasiResource;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Ekspor\EksporDaftar;
use App\Shared\Infrastructure\Ekspor\KolomEkspor;
use App\Shared\Infrastructure\Persistence\BacaRelasi;
use App\Shared\Infrastructure\Qr\PembuatQrAset;
use App\Shared\Infrastructure\Validasi\AturanWajib;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AsetController extends Controller
{
    public function __construct(private readonly LayananAudit $audit) {}

    /**
     * Nama kolom tidak pernah datang dari permintaan; hanya kunci yang terdaftar
     * di sini yang diteruskan ke SQL.
     *
     * @var array<string, list<string>>
     */
    private const ATURAN_FILTER = [
        'cari' => ['nullable', 'string'],
        'kategoriAsetId' => ['nullable', 'string'],
        'lokasiId' => ['nullable', 'string'],
        'status' => ['nullable', 'string'],
        'urutkan' => ['nullable', 'string', 'in:Nama,KodeAset,Status,DibuatPada'],
        'arah' => ['nullable', 'string', 'in:asc,desc'],
        'format' => ['nullable', 'string', 'in:Csv,Xlsx,Pdf,csv,xlsx,pdf'],
    ];

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Aset::class);

        $filter = $request->validate(self::ATURAN_FILTER);

        $aset = $this->kueriTersaring($filter)->paginate(25)->withQueryString();

        return Inertia::render('Aset/Index', [
            'aset' => AsetResource::collection($aset),
            'filter' => $filter,
            // Dikirim dari server supaya batas di tombol cetak tidak pernah beda dengan validasinya.
            'maksLabel' => CetakLabelAsetRequest::MAKS_LABEL,
            'wajib' => ['aset' => AturanWajib::untuk(SimpanAsetRequest::class)],
            'kategoriAset' => KategoriAsetResource::collection(KategoriAset::query()->orderBy('Nama')->get()),
            'lokasi' => LokasiResource::collection(Lokasi::query()->orderBy('Nama')->get()),
        ]);
    }

    /**
     * Penyaring daftar aset, dipakai bersama oleh halaman dan ekspornya.
     *
     * @param  array<string, mixed>  $filter
     * @return Builder<Aset>
     */
    private function kueriTersaring(array $filter): Builder
    {
        $urutkan = $filter['urutkan'] ?? 'DibuatPada';
        $arah = $filter['arah'] ?? 'desc';

        return Aset::query()
            ->with(['kategoriAset', 'lokasi', 'modelAset', 'unitOrganisasi'])
            ->when($filter['cari'] ?? null, fn ($q, $v) => $q->where(fn ($qq) => $qq
                ->where('Nama', 'like', "%{$v}%")
                ->orWhere('KodeAset', 'like', "%{$v}%")
                ->orWhere('NomorSeri', 'like', "%{$v}%")))
            ->when($filter['kategoriAsetId'] ?? null, fn ($q, $v) => $q->where('KategoriAsetId', $v))
            ->when($filter['lokasiId'] ?? null, fn ($q, $v) => $q->where('LokasiId', $v))
            ->when($filter['status'] ?? null, fn ($q, $v) => $q->where('Status', $v))
            ->orderBy($urutkan, $arah)
            // Pemutus seri. Tanpa urutan yang pasti, dua aset dengan nilai urut
            // sama boleh ditukar MySQL antar permintaan -- satu baris muncul di
            // dua halaman, baris lain tidak muncul sama sekali, dan ekspor yang
            // membaca per potongan ikut melewatkannya.
            ->orderBy('Id');
    }

    /** Register aset seperti yang tampil di layar, lengkap dengan penyaringnya. */
    public function ekspor(Request $request, EksporDaftar $ekspor): StreamedResponse
    {
        $this->authorize('viewAny', Aset::class);

        $filter = $request->validate(self::ATURAN_FILTER);

        $this->audit->catat('Aset.Diekspor', 'Aset', null, dataSesudah: $filter);

        return $ekspor->unduh(
            $this->kueriTersaring($filter),
            [
                KolomEkspor::atribut('Kode Aset', 'KodeAset'),
                KolomEkspor::atribut('Nama', 'Nama'),
                KolomEkspor::dari('Kategori', fn (Aset $a): string => BacaRelasi::teks(BacaRelasi::model($a, 'kategoriAset'), 'Nama')),
                KolomEkspor::dari('Merek/Model', fn (Aset $a): string => BacaRelasi::teks(BacaRelasi::model($a, 'modelAset'), 'Nama')),
                KolomEkspor::atribut('Nomor Seri', 'NomorSeri'),
                KolomEkspor::atribut('Nomor Inventaris', 'NomorInventaris'),
                KolomEkspor::dari('Unit', fn (Aset $a): string => BacaRelasi::teks(BacaRelasi::model($a, 'unitOrganisasi'), 'Nama')),
                KolomEkspor::dari('Lokasi', fn (Aset $a): string => BacaRelasi::teks(BacaRelasi::model($a, 'lokasi'), 'Nama')),
                KolomEkspor::atribut('Status', 'Status'),
                KolomEkspor::atribut('Kondisi', 'Kondisi'),
                KolomEkspor::atribut('Tingkat Kritis', 'TingkatKritis'),
                KolomEkspor::tanggal('Tanggal Perolehan', 'TanggalPerolehan'),
                KolomEkspor::atribut('Harga Perolehan', 'HargaPerolehan'),
                KolomEkspor::atribut('Sumber Dana', 'SumberDana'),
            ],
            'daftar-aset',
            EksporDaftar::formatDari($request),
        );
    }

    public function show(Aset $aset, PembuatQrAset $pembuat): Response
    {
        $this->authorize('view', $aset);

        $aset->load(['kategoriAset', 'modelAset.merek', 'alkesAspak', 'lokasi', 'unitOrganisasi', 'penyedia', 'dibuatOleh']);

        return Inertia::render('Aset/Show', [
            'aset' => new AsetResource($aset),
            // KodeQr dulu hanya ditampilkan sebagai teks, jadi tidak pernah bisa dipindai.
            'qr' => $aset->KodeQr === null ? null : $pembuat->untuk([$aset->KodeQr], 1)[0]['Svg'],
            'wajib' => ['aset' => AturanWajib::untuk(SimpanAsetRequest::class)],
            'kategoriAset' => KategoriAsetResource::collection(KategoriAset::query()->orderBy('Nama')->get()),
            'modelAset' => ModelAsetResource::collection(ModelAset::query()->orderBy('Nama')->get()),
            'penyedia' => PenyediaResource::collection(Penyedia::query()->orderBy('Nama')->get()),
            'unitOrganisasi' => UnitOrganisasiResource::collection(UnitOrganisasi::query()->where('Status', 'Aktif')->orderBy('Nama')->get()),
            'lokasi' => LokasiResource::collection(Lokasi::query()->orderBy('Nama')->get()),
        ]);
    }

    public function store(SimpanAsetRequest $request, BuatAset $aksi): RedirectResponse
    {
        $this->authorize('create', Aset::class);

        $aset = $aksi->jalankan($request->validated(), $request->user('web')->Id);

        return redirect("/aset/{$aset->Id}")->with('sukses', 'Aset berhasil didaftarkan.');
    }

    public function update(SimpanAsetRequest $request, Aset $aset, UbahAset $aksi): RedirectResponse
    {
        $this->authorize('update', $aset);

        $aksi->jalankan($aset, $request->validated());

        return back()->with('sukses', 'Aset berhasil diperbarui.');
    }

    public function destroy(Aset $aset, HapusAset $aksi): RedirectResponse
    {
        $this->authorize('delete', $aset);

        $aksi->jalankan($aset);

        return redirect('/aset')->with('sukses', 'Aset berhasil diarsipkan.');
    }
}
