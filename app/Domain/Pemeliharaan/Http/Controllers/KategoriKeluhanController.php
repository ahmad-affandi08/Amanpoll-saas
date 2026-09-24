<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Controllers;

use App\Domain\Pemeliharaan\Application\Actions\HapusKategoriKeluhan;
use App\Domain\Pemeliharaan\Application\Actions\SimpanKategoriKeluhan;
use App\Domain\Pemeliharaan\Http\Requests\SimpanKategoriKeluhanRequest;
use App\Domain\Pemeliharaan\Http\Resources\KategoriKeluhanResource;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KategoriKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\TingkatLayanan;
use App\Domain\Platform\Application\Services\OpsiUnitPengelola;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Ekspor\EksporDaftar;
use App\Shared\Infrastructure\Ekspor\KolomEkspor;
use App\Shared\Infrastructure\Persistence\BacaRelasi;
use App\Shared\Infrastructure\Persistence\DaftarTersaring;
use App\Shared\Infrastructure\Validasi\AturanWajib;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class KategoriKeluhanController extends Controller
{
    /**
     * Penyaring daftar, dipakai bersama halaman dan ekspornya.
     *
     * @return DaftarTersaring<KategoriKeluhan>
     */
    private function daftar(Request $request): DaftarTersaring
    {
        return DaftarTersaring::untuk(
            $request,
            KategoriKeluhan::query()->with(['induk', 'tingkatLayanan', 'peranPenanggungJawab', 'unitPengelola:Id,Kode,Nama']),
        )
            ->cari(['Kode', 'Nama'])
            ->urut(['Nama', 'Kode', 'PrioritasBawaan'], bawaan: 'Nama')
            ->faset(['PrioritasBawaan']);
    }

    public function ekspor(Request $request, EksporDaftar $ekspor): StreamedResponse
    {
        $this->authorize('viewAny', KategoriKeluhan::class);

        return $ekspor->unduh(
            $this->daftar($request)->kueriTersaring(),
            [
                KolomEkspor::atribut('Kode', 'Kode'),
                KolomEkspor::atribut('Nama', 'Nama'),
                KolomEkspor::atribut('Prioritas Bawaan', 'PrioritasBawaan'),
                KolomEkspor::dari('Aset Wajib', fn (KategoriKeluhan $k): string => $k->AsetWajib ? 'Ya' : 'Tidak'),
                ...(OpsiUnitPengelola::dipakai()
                    ? [KolomEkspor::dari('Unit Pengelola', fn (KategoriKeluhan $k): string => BacaRelasi::teks(BacaRelasi::model($k, 'unitPengelola'), 'Nama'))]
                    : []),
                KolomEkspor::dari('Aktif', fn (KategoriKeluhan $k): string => $k->Aktif ? 'Ya' : 'Tidak'),
            ],
            'daftar-kategori-keluhan',
            EksporDaftar::formatDari($request),
        );
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', KategoriKeluhan::class);

        $daftar = $this->daftar($request);
        $pakaiUnitPengelola = OpsiUnitPengelola::dipakai();
        $semuaUnitPengelola = $pakaiUnitPengelola ? OpsiUnitPengelola::daftar(termasukNonaktif: true) : [];
        $semuaKategori = $pakaiUnitPengelola
            ? KategoriKeluhan::query()->get(['Id', 'IndukId', 'UnitPengelolaId', 'Aktif'])->keyBy('Id')
            : new Collection;
        $warisan = $this->unitPengelolaWarisan($semuaKategori);
        $namaUnit = array_column($semuaUnitPengelola, null, 'Id');

        return Inertia::render('KategoriKeluhan/Index', [
            'wajib' => ['kategoriKeluhan' => AturanWajib::untuk(SimpanKategoriKeluhanRequest::class)],
            'kategori' => KategoriKeluhanResource::collection($daftar->halaman()),
            'filter' => $daftar->filterBerlaku(),
            // Pemilih induk harus memuat seluruh kategori, bukan hanya yang tampil di halaman ini.
            'pilihanInduk' => KategoriKeluhan::query()->orderBy('Nama')->get(['Id', 'Nama']),
            'tingkatLayanan' => TingkatLayanan::query()->where('Aktif', true)->orderBy('Nama')->get(['Id', 'Nama']),
            'peran' => Peran::query()->orderBy('Nama')->get(['Id', 'Nama']),
            // Unit pengelola (PRD 8.21) hanya tampil bagi organisasi yang memakainya.
            'pakaiUnitPengelola' => $pakaiUnitPengelola,
            'pilihanUnitPengelola' => $pakaiUnitPengelola ? OpsiUnitPengelola::daftar() : [],
            // Unit yang diwarisi dari induk, untuk kategori yang kolomnya sendiri kosong.
            'unitPengelolaWarisan' => array_filter(array_map(
                fn (?string $unitId): ?array => $unitId === null ? null : ($namaUnit[$unitId] ?? null),
                $warisan,
            )),
            // Kategori aktif yang tidak meneruskan keluhan ke antrean mana pun kecuali lewat aset.
            'jumlahTanpaUnitPengelola' => count(array_filter(
                $warisan,
                fn (?string $unitId, string $kategoriId): bool => $unitId === null && $semuaKategori->get($kategoriId)?->Aktif === true,
                ARRAY_FILTER_USE_BOTH,
            )),
        ]);
    }

    /**
     * Unit pengelola efektif setiap kategori yang kolomnya sendiri kosong:
     * naik ke induk sampai ketemu, seperti `PenentuUnitPengelola::untukKeluhan`,
     * dalam satu kueri untuk seluruh kategori organisasi.
     *
     * Kategori yang unitnya null di sini hanya meneruskan keluhan ke antrean
     * unit pengelola lewat asetnya; tanpa aset, keluhannya hanya terlihat
     * oleh pengguna yang lingkupnya mencakup lokasinya atau tanpa batas.
     *
     * @param  Collection<string, KategoriKeluhan>  $semua  seluruh kategori organisasi, berkunci Id
     * @return array<string, string|null> KategoriId => UnitPengelolaId warisan (null: tidak ada)
     */
    private function unitPengelolaWarisan(Collection $semua): array
    {
        $hasil = [];

        foreach ($semua as $kategori) {
            if ($kategori->UnitPengelolaId !== null) {
                continue;
            }

            $unitId = null;
            $dikunjungi = [$kategori->Id => true];
            $indukId = $kategori->IndukId;

            // Batas 20 tingkat dan daftar kunjungan menghentikan hierarki yang rusak.
            for ($tingkat = 0; $tingkat < 20 && $indukId !== null && ! isset($dikunjungi[$indukId]); $tingkat++) {
                $induk = $semua->get($indukId);
                if ($induk === null) {
                    break;
                }

                if ($induk->UnitPengelolaId !== null) {
                    $unitId = $induk->UnitPengelolaId;
                    break;
                }

                $dikunjungi[$indukId] = true;
                $indukId = $induk->IndukId;
            }

            $hasil[$kategori->Id] = $unitId;
        }

        return $hasil;
    }

    public function store(SimpanKategoriKeluhanRequest $request, SimpanKategoriKeluhan $aksi): RedirectResponse
    {
        $this->authorize('create', KategoriKeluhan::class);
        $aksi->jalankan($request->validated());

        return back()->with('sukses', 'Kategori keluhan berhasil dibuat.');
    }

    public function update(SimpanKategoriKeluhanRequest $request, KategoriKeluhan $kategoriKeluhan, SimpanKategoriKeluhan $aksi): RedirectResponse
    {
        $this->authorize('update', $kategoriKeluhan);
        $aksi->jalankan($request->validated(), $kategoriKeluhan);

        return back()->with('sukses', 'Kategori keluhan berhasil diperbarui.');
    }

    public function destroy(KategoriKeluhan $kategoriKeluhan, HapusKategoriKeluhan $aksi): RedirectResponse
    {
        $this->authorize('delete', $kategoriKeluhan);
        $aksi->jalankan($kategoriKeluhan);

        return back()->with('sukses', 'Kategori keluhan berhasil dihapus.');
    }
}
