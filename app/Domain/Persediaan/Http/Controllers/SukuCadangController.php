<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Http\Controllers;

use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset as KategoriAsetModel;
use App\Domain\Aset\Infrastructure\Persistence\Models\ModelAset;
use App\Domain\Persediaan\Application\Actions\BuatKelompokSukuCadang;
use App\Domain\Persediaan\Application\Actions\BuatSukuCadang;
use App\Domain\Persediaan\Application\Actions\HapusKelompokSukuCadang;
use App\Domain\Persediaan\Application\Actions\HapusSukuCadang;
use App\Domain\Persediaan\Application\Actions\UbahKelompokSukuCadang;
use App\Domain\Persediaan\Application\Actions\UbahSukuCadang;
use App\Domain\Persediaan\Domain\Enums\StatusReservasiSukuCadang;
use App\Domain\Persediaan\Http\Requests\SimpanKelompokSukuCadangRequest;
use App\Domain\Persediaan\Http\Requests\SimpanKompatibilitasSukuCadangRequest;
use App\Domain\Persediaan\Http\Requests\SimpanSukuCadangRequest;
use App\Domain\Persediaan\Http\Resources\KelompokSukuCadangResource;
use App\Domain\Persediaan\Http\Resources\KompatibilitasSukuCadangResource;
use App\Domain\Persediaan\Http\Resources\SukuCadangResource;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\KategoriSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\KelompokSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\PemakaianSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\ReservasiSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\StokSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Ekspor\EksporDaftar;
use App\Shared\Infrastructure\Ekspor\KolomEkspor;
use App\Shared\Infrastructure\Persistence\BacaRelasi;
use App\Shared\Infrastructure\Persistence\DaftarTersaring;
use App\Shared\Infrastructure\Validasi\AturanWajib;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class SukuCadangController extends Controller
{
    /** Riwayat pemakaian dapat panjang; dipotong dan jumlah seluruhnya tetap disebut. */
    private const MAKS_RIWAYAT = 50;

    /**
     * Penyaring daftar suku cadang, dipakai bersama halaman dan ekspornya.
     *
     * Kueri dasarnya dioper karena keduanya memilih kolom yang berbeda: halaman
     * menjumlahkan stok hanya untuk baris yang tampil, sedangkan ekspor perlu
     * stok setiap baris.
     *
     * @param  Builder<SukuCadang>  $kueri
     * @return DaftarTersaring<SukuCadang>
     */
    private function daftar(Request $request, Builder $kueri): DaftarTersaring
    {
        return DaftarTersaring::untuk($request, $kueri)
            ->cari(['Kode', 'Nama'])
            // Hanya kolom nyata; Kategori dan Stok Tersedia turunan, jadi tidak dapat diurutkan server.
            ->urut(['Nama', 'Status'], bawaan: 'Nama')
            ->faset(['KategoriSukuCadangId']);
    }

    /** Daftar suku cadang beserta stok bersihnya, seperti yang tampil di layar. */
    public function ekspor(Request $request, EksporDaftar $ekspor): StreamedResponse
    {
        $this->authorize('viewAny', SukuCadang::class);

        // Stok dihitung lewat subkueri, bukan per halaman: ekspor tidak punya
        // halaman, dan laporan persediaan tanpa angka stok tidak ada gunanya.
        $kueri = SukuCadang::query()
            ->with('kategoriSukuCadang')
            ->select('SukuCadang.*')
            ->selectSub(
                DB::table('StokSukuCadang')
                    ->selectRaw('COALESCE(SUM(JumlahTersedia) - SUM(JumlahDitahan), 0)')
                    ->whereColumn('StokSukuCadang.SukuCadangId', 'SukuCadang.Id'),
                'JumlahTersediaBersih',
            );

        return $ekspor->unduh(
            $this->daftar($request, $kueri)->kueriTersaring(),
            [
                KolomEkspor::atribut('Kode', 'Kode'),
                KolomEkspor::atribut('Nama', 'Nama'),
                KolomEkspor::dari('Kategori', fn (SukuCadang $s): string => BacaRelasi::teks(BacaRelasi::model($s, 'kategoriSukuCadang'), 'Nama')),
                KolomEkspor::atribut('Nomor Bagian', 'NomorBagian'),
                KolomEkspor::atribut('Satuan', 'SatuanDasar'),
                KolomEkspor::atribut('Stok Tersedia', 'JumlahTersediaBersih'),
                KolomEkspor::atribut('Stok Minimum', 'StokMinimum'),
                KolomEkspor::atribut('Stok Maksimum', 'StokMaksimum'),
                KolomEkspor::atribut('Titik Pesan Ulang', 'TitikPesanUlang'),
                KolomEkspor::atribut('Harga Rata-rata', 'HargaRataRata'),
                KolomEkspor::atribut('Status', 'Status'),
            ],
            'daftar-suku-cadang',
            EksporDaftar::formatDari($request),
        );
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', SukuCadang::class);

        $daftar = $this->daftar($request, SukuCadang::query()->with('kategoriSukuCadang'));

        $halaman = $daftar->halaman();

        // Stok dijumlahkan hanya untuk baris yang benar-benar tampil di halaman ini.
        $agregatStok = DB::table('StokSukuCadang')
            ->select('SukuCadangId', DB::raw('SUM(JumlahTersedia) - SUM(JumlahDitahan) as bersih'))
            ->whereIn('SukuCadangId', $halaman->getCollection()->pluck('Id'))
            ->groupBy('SukuCadangId')
            ->pluck('bersih', 'SukuCadangId');

        $halaman->getCollection()->each(function (SukuCadang $s) use ($agregatStok): void {
            $s->setAttribute('JumlahTersediaBersih', (float) ($agregatStok[$s->Id] ?? 0));
        });

        return Inertia::render('SukuCadang/Index', [
            'wajib' => ['sukuCadang' => AturanWajib::untuk(SimpanSukuCadangRequest::class)],
            'sukuCadang' => SukuCadangResource::collection($halaman),
            'filter' => $daftar->filterBerlaku(),
            'jumlahDibawahMinimum' => $this->jumlahDibawahMinimum(),
            'kategoriSukuCadang' => KategoriSukuCadang::query()->orderBy('Nama')->get(['Id', 'Nama']),
        ]);
    }

    /**
     * Dihitung di basis data, bukan dari baris yang kebetulan tampil.
     *
     * Halaman ini dipaginasi, jadi menghitungnya dari koleksi di tangan akan
     * melaporkan angka yang mengecil setiap kali pengguna berpindah halaman.
     */
    private function jumlahDibawahMinimum(): int
    {
        $saldo = DB::table('StokSukuCadang')
            ->select('SukuCadangId', DB::raw('SUM(JumlahTersedia) - SUM(JumlahDitahan) as bersih'))
            ->groupBy('SukuCadangId');

        return SukuCadang::query()
            ->leftJoinSub($saldo, 'saldo', 'saldo.SukuCadangId', '=', 'SukuCadang.Id')
            ->whereRaw('COALESCE(saldo.bersih, 0) <= SukuCadang.StokMinimum')
            ->count();
    }

    public function show(SukuCadang $sukuCadang): Response
    {
        $this->authorize('view', $sukuCadang);

        $sukuCadang->load(['kategoriSukuCadang', 'kompatibilitasSukuCadang.kategoriAset', 'kompatibilitasSukuCadang.modelAset', 'kompatibilitasSukuCadang.aset']);
        $kelompok = KelompokSukuCadang::query()->where('SukuCadangId', $sukuCadang->Id)->orderByDesc('DibuatPada')->get();

        return Inertia::render('SukuCadang/Show', [
            'wajib' => ['kelompok' => AturanWajib::untuk(SimpanKelompokSukuCadangRequest::class), 'kompatibilitas' => AturanWajib::untuk(SimpanKompatibilitasSukuCadangRequest::class)],
            'sukuCadang' => new SukuCadangResource($sukuCadang),
            'stok' => $this->stokPerGudang($sukuCadang),
            'pemakaian' => $this->pemakaianTerakhir($sukuCadang),
            'reservasi' => $this->reservasiAktif($sukuCadang),
            'kelompokSukuCadang' => KelompokSukuCadangResource::collection($kelompok),
            'kompatibilitasSukuCadang' => KompatibilitasSukuCadangResource::collection($sukuCadang->kompatibilitasSukuCadang),
            'kategoriAset' => KategoriAsetModel::query()->orderBy('Nama')->get(['Id', 'Nama']),
            'modelAset' => ModelAset::query()->orderBy('Nama')->get(['Id', 'Nama']),
            'aset' => Aset::query()->orderBy('Nama')->get(['Id', 'Nama', 'KodeAset']),
        ]);
    }

    /**
     * Saldo per gudang, lokasi rak, dan batch.
     *
     * Inilah angka yang dicari orang saat membuka suku cadang, dan justru itu
     * yang selama ini tidak ada di halaman ini -- hanya stok minimumnya.
     *
     * @return array{baris: list<array<string, mixed>>, TotalTersedia: float, TotalDitahan: float, TotalBersih: float}
     */
    private function stokPerGudang(SukuCadang $sukuCadang): array
    {
        $baris = $sukuCadang->stok()
            ->with(['gudang', 'lokasiGudang', 'kelompokSukuCadang'])
            ->get()
            ->map(fn (StokSukuCadang $satu): array => [
                'Id' => $satu->Id,
                'Gudang' => $satu->gudang?->Nama,
                'LokasiGudang' => $satu->lokasiGudang?->Nama,
                'NomorBatch' => $satu->kelompokSukuCadang?->NomorBatch,
                'JumlahTersedia' => (float) $satu->JumlahTersedia,
                'JumlahDitahan' => (float) $satu->JumlahDitahan,
                'JumlahBersih' => (float) $satu->JumlahTersedia - (float) $satu->JumlahDitahan,
            ])
            ->all();

        $baris = array_values($baris);

        return [
            'baris' => $baris,
            'TotalTersedia' => (float) array_sum(array_column($baris, 'JumlahTersedia')),
            'TotalDitahan' => (float) array_sum(array_column($baris, 'JumlahDitahan')),
            'TotalBersih' => (float) array_sum(array_column($baris, 'JumlahBersih')),
        ];
    }

    /**
     * @return array{total: int, data: list<array<string, mixed>>}
     */
    private function pemakaianTerakhir(SukuCadang $sukuCadang): array
    {
        return [
            'total' => (int) $sukuCadang->pemakaian()->count(),
            'data' => array_values($sukuCadang->pemakaian()
                ->with(['perintahKerja', 'gudang', 'dipakaiOleh'])
                ->limit(self::MAKS_RIWAYAT)
                ->get()
                ->map(fn (PemakaianSukuCadang $satu): array => [
                    'Id' => $satu->Id,
                    'PerintahKerjaId' => $satu->PerintahKerjaId,
                    'NomorPerintahKerja' => $satu->perintahKerja?->Nomor,
                    'JudulPerintahKerja' => $satu->perintahKerja?->Judul,
                    'Gudang' => $satu->gudang?->Nama,
                    'Jumlah' => (float) $satu->Jumlah,
                    'HargaSatuan' => $satu->HargaSatuan === null ? null : (float) $satu->HargaSatuan,
                    'DipakaiOleh' => $satu->dipakaiOleh?->Nama,
                    'DipakaiPada' => $satu->DipakaiPada->toIso8601String(),
                ])
                ->all()),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function reservasiAktif(SukuCadang $sukuCadang): array
    {
        return array_values($sukuCadang->reservasi()
            ->where('Status', StatusReservasiSukuCadang::Aktif->value)
            ->with(['perintahKerja', 'gudang'])
            ->limit(self::MAKS_RIWAYAT)
            ->get()
            ->map(fn (ReservasiSukuCadang $satu): array => [
                'Id' => $satu->Id,
                'PerintahKerjaId' => $satu->PerintahKerjaId,
                'NomorPerintahKerja' => $satu->perintahKerja?->Nomor,
                'Gudang' => $satu->gudang?->Nama,
                'Jumlah' => (float) $satu->Jumlah,
                'KadaluarsaPada' => $satu->KadaluarsaPada?->toIso8601String(),
            ])
            ->all());
    }

    public function store(SimpanSukuCadangRequest $request, BuatSukuCadang $aksi): RedirectResponse
    {
        $this->authorize('create', SukuCadang::class);

        $sukuCadang = $aksi->jalankan($request->validated());

        return redirect("/suku-cadang/{$sukuCadang->Id}")->with('sukses', 'Suku cadang berhasil dibuat.');
    }

    public function update(SimpanSukuCadangRequest $request, SukuCadang $sukuCadang, UbahSukuCadang $aksi): RedirectResponse
    {
        $this->authorize('update', $sukuCadang);

        $aksi->jalankan($sukuCadang, $request->validated());

        return back()->with('sukses', 'Suku cadang berhasil diperbarui.');
    }

    public function destroy(SukuCadang $sukuCadang, HapusSukuCadang $aksi): RedirectResponse
    {
        $this->authorize('delete', $sukuCadang);

        $aksi->jalankan($sukuCadang);

        return redirect('/suku-cadang')->with('sukses', 'Suku cadang berhasil dihapus.');
    }

    public function storeKelompok(SimpanKelompokSukuCadangRequest $request, SukuCadang $sukuCadang, BuatKelompokSukuCadang $aksi): RedirectResponse
    {
        $this->authorize('update', $sukuCadang);

        $data = $request->validated();
        $data['SukuCadangId'] = $sukuCadang->Id;
        $aksi->jalankan($data);

        return back()->with('sukses', 'Kelompok/batch suku cadang berhasil dibuat.');
    }

    public function updateKelompok(SimpanKelompokSukuCadangRequest $request, KelompokSukuCadang $kelompokSukuCadang, UbahKelompokSukuCadang $aksi): RedirectResponse
    {
        $this->authorize('update', $kelompokSukuCadang->sukuCadang);

        $aksi->jalankan($kelompokSukuCadang, $request->validated());

        return back()->with('sukses', 'Kelompok/batch suku cadang berhasil diperbarui.');
    }

    public function destroyKelompok(KelompokSukuCadang $kelompokSukuCadang, HapusKelompokSukuCadang $aksi): RedirectResponse
    {
        $this->authorize('update', $kelompokSukuCadang->sukuCadang);

        $aksi->jalankan($kelompokSukuCadang);

        return back()->with('sukses', 'Kelompok/batch suku cadang berhasil dihapus.');
    }
}
