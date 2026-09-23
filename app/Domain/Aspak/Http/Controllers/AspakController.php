<?php

declare(strict_types=1);

namespace App\Domain\Aspak\Http\Controllers;

use App\Core\Audit\LayananAudit;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\ModelAset;
use App\Domain\Aspak\Application\Actions\ImporKatalogAspak;
use App\Domain\Aspak\Application\Services\PenyusunBarisAspak;
use App\Domain\Aspak\Domain\ValueObjects\ProfilKolomAspak;
use App\Domain\Aspak\Http\Requests\ImporKatalogAspakRequest;
use App\Domain\Aspak\Http\Requests\SimpanPemetaanAspakRequest;
use App\Domain\Aspak\Http\Resources\AlkesAspakResource;
use App\Domain\Aspak\Http\Resources\PemetaanAspakResource;
use App\Domain\Aspak\Infrastructure\Persistence\Models\AlkesAspak;
use App\Domain\Aspak\Infrastructure\Persistence\Models\PemetaanAspak;
use App\Domain\Platform\Infrastructure\Persistence\Models\KonfigurasiOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Persistence\DaftarTersaring;
use App\Shared\Infrastructure\Validasi\AturanWajib;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Pertukaran data dengan ASPAK (Aplikasi Sarana, Prasarana, dan Alat Kesehatan).
 *
 * ASPAK bertukar data lewat berkas, bukan API, jadi modul ini tidak memakai
 * kerangka AdapterSinkronisasi yang berbasis REST: katalog nomenklaturnya
 * diimpor sebagai CSV, dan data aset dikeluarkan sebagai CSV untuk diunggah
 * kembali ke ASPAK.
 */
final class AspakController extends Controller
{
    /** Karakter yang membuat Excel memperlakukan sel sebagai rumus. */
    private const AWALAN_RUMUS = "=+-@\t\r";

    public const KUNCI_PROFIL = 'Aspak.ProfilKolom';

    /** Cukup untuk dipindai mata; sisanya disaring dengan mengetik lebih spesifik. */
    private const MAKS_HASIL_CARI = 50;

    public function __construct(private readonly LayananAudit $audit) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', AlkesAspak::class);

        $daftar = DaftarTersaring::untuk($request, AlkesAspak::query()->withCount('pemetaan'))
            ->cari(['Kode', 'Nama', 'Kelompok'])
            ->urut(['Kode', 'Nama', 'Kelompok'], bawaan: 'Kode');

        return Inertia::render('Aspak/Index', [
            'alkes' => AlkesAspakResource::collection($daftar->halaman()),
            'filter' => $daftar->filterBerlaku(),
            'pemetaan' => PemetaanAspakResource::collection(
                PemetaanAspak::query()->with(['alkes', 'kategoriAset', 'modelAset'])->get(),
            ),
            'kategoriAset' => KategoriAset::query()->orderBy('Nama')->get(['Id', 'Nama']),
            'modelAset' => ModelAset::query()->orderBy('Nama')->get(['Id', 'Nama']),
            'ringkasan' => $this->ringkasan(),
            'kolomEkspor' => $this->profil()->judul(),
            'wajib' => ['pemetaan' => AturanWajib::untuk(SimpanPemetaanAspakRequest::class)],
        ]);
    }

    /**
     * Angka kesiapan ekspor, dihitung di basis data supaya tidak bergantung
     * pada halaman katalog yang kebetulan sedang tampil.
     *
     * @return array{JumlahAlkes: int, JumlahPemetaan: int, AsetTerpetakan: int, AsetBelumTerpetakan: int, LokasiTanpaKodeRuang: int}
     */
    private function ringkasan(): array
    {
        $kategoriTerpetakan = PemetaanAspak::query()->whereNotNull('KategoriAsetId')->pluck('KategoriAsetId');
        $modelTerpetakan = PemetaanAspak::query()->whereNotNull('ModelAsetId')->pluck('ModelAsetId');

        $terpetakan = Aset::query()
            ->where(function ($kueri) use ($kategoriTerpetakan, $modelTerpetakan): void {
                // Nomenklatur yang dipilih langsung di aset ikut dihitung; ia
                // menang atas pemetaan model maupun kategori.
                $kueri->whereNotNull('AlkesAspakId')
                    ->orWhereIn('ModelAsetId', $modelTerpetakan)
                    ->orWhereIn('KategoriAsetId', $kategoriTerpetakan);
            })
            ->count();

        return [
            'JumlahAlkes' => AlkesAspak::query()->count(),
            'JumlahPemetaan' => PemetaanAspak::query()->count(),
            'AsetTerpetakan' => $terpetakan,
            'AsetBelumTerpetakan' => Aset::query()->count() - $terpetakan,
            'LokasiTanpaKodeRuang' => Lokasi::query()
                ->where(fn ($kueri) => $kueri->whereNull('KodeRuangAspak')->orWhere('KodeRuangAspak', ''))
                ->count(),
        ];
    }

    /**
     * Pencarian katalog untuk pemilih nomenklatur di formulir aset.
     *
     * Dijawab per permintaan, bukan dikirim penuh bersama halaman: katalog
     * ASPAK berisi ribuan alkes dan menyertakannya di setiap muat halaman aset
     * membuat halamannya berat tanpa alasan.
     *
     * Diizinkan bagi siapa pun yang boleh melihat aset, bukan hanya pengelola
     * ASPAK -- yang mendaftarkan aset justru petugas ruangan.
     *
     * @return array<int, array{Id: string, Kode: string, Nama: string, Kelompok: string|null}>
     */
    public function cariKatalog(Request $request): array
    {
        $this->authorize('viewAny', Aset::class);

        $kueri = trim((string) $request->query('q', ''));

        return AlkesAspak::query()
            ->where('Aktif', true)
            ->when($kueri !== '', function ($q) use ($kueri): void {
                $q->where(function ($cari) use ($kueri): void {
                    $cari->where('Kode', 'like', '%'.$kueri.'%')
                        ->orWhere('Nama', 'like', '%'.$kueri.'%');
                });
            })
            ->orderBy('Nama')
            ->limit(self::MAKS_HASIL_CARI)
            ->get(['Id', 'Kode', 'Nama', 'Kelompok'])
            ->map(fn (AlkesAspak $satu): array => [
                'Id' => (string) $satu->Id,
                'Kode' => (string) $satu->Kode,
                'Nama' => (string) $satu->Nama,
                'Kelompok' => $satu->Kelompok,
            ])
            ->all();
    }

    public function impor(ImporKatalogAspakRequest $request, ImporKatalogAspak $aksi): RedirectResponse
    {
        $this->authorize('create', AlkesAspak::class);

        $jalur = $request->file('Berkas')?->getRealPath();

        if (! is_string($jalur)) {
            return back()->with('gagal', 'Berkas tidak dapat dibaca.');
        }

        $pegangan = fopen($jalur, 'rb');

        if ($pegangan === false) {
            return back()->with('gagal', 'Berkas tidak dapat dibuka.');
        }

        try {
            $hasil = $aksi->jalankan($pegangan);
        } finally {
            fclose($pegangan);
        }

        $this->audit->catat('Aspak.KatalogDiimpor', 'AlkesAspak', null, dataSesudah: $hasil);

        return back()->with('sukses', sprintf(
            'Impor katalog selesai: %d alkes baru, %d diperbarui, %d baris dilewati.',
            $hasil['ditambah'],
            $hasil['diperbarui'],
            $hasil['dilewati'],
        ));
    }

    public function simpanPemetaan(SimpanPemetaanAspakRequest $request): RedirectResponse
    {
        $this->authorize('create', PemetaanAspak::class);

        $data = $request->validated();
        $kunci = blank($data['ModelAsetId'] ?? null)
            ? ['KategoriAsetId' => $data['KategoriAsetId'], 'ModelAsetId' => null]
            : ['KategoriAsetId' => null, 'ModelAsetId' => $data['ModelAsetId']];

        // Satu kategori/model hanya boleh menunjuk satu kode; memetakan ulang
        // berarti mengganti, bukan menambah baris kedua.
        PemetaanAspak::query()->updateOrCreate($kunci, ['AlkesAspakId' => $data['AlkesAspakId']]);

        return back()->with('sukses', 'Pemetaan ASPAK berhasil disimpan.');
    }

    public function hapusPemetaan(PemetaanAspak $pemetaan): RedirectResponse
    {
        $this->authorize('delete', $pemetaan);

        $pemetaan->delete();

        return back()->with('sukses', 'Pemetaan ASPAK berhasil dihapus.');
    }

    /**
     * Aset kita dalam susunan kolom ASPAK.
     *
     * Aset yang belum dipetakan sengaja tidak ikut: ASPAK menolak baris tanpa
     * kode alat, dan mengirim baris kosong lebih buruk daripada tidak mengirim
     * -- angkanya terlanjur tercatat salah di sana.
     */
    public function ekspor(PenyusunBarisAspak $penyusun): StreamedResponse
    {
        $this->authorize('viewAny', AlkesAspak::class);

        $profil = $this->profil();
        $ruas = $profil->ruas();

        $this->audit->catat('Aspak.Diekspor', 'Aset', null, dataSesudah: [
            'Kolom' => count($ruas),
        ]);

        // Isi unduhan baru dihasilkan sesudah middleware selesai, dan
        // TetapkanKonteksOrganisasi membersihkan konteksnya di blok finally.
        // Tanpa menetapkan ulang di dalam closure, ScopeOrganisasi menutup
        // seluruh kueri (fail-closed) dan berkasnya keluar hanya berisi kepala
        // kolom -- terlihat berhasil, padahal kosong.
        $organisasiId = app(KonteksOrganisasi::class)->wajibId();

        return response()->streamDownload(function () use ($penyusun, $profil, $ruas, $organisasiId): void {
            $keluaran = fopen('php://output', 'wb');

            if ($keluaran === false) {
                return;
            }

            $konteks = app(KonteksOrganisasi::class);
            $konteks->tetapkan($organisasiId);

            try {
                fwrite($keluaran, "\xEF\xBB\xBF");
                fputcsv($keluaran, $profil->judul(), escape: '\\');

                Aset::query()
                    ->with(['lokasi', 'modelAset.merek', 'alkesAspak', 'pelaksanaanKalibrasi'])
                    ->orderBy('KodeAset')
                    ->chunk(300, function ($kumpulan) use ($keluaran, $penyusun, $ruas): void {
                        foreach ($kumpulan as $aset) {
                            if (! $penyusun->terpetakan($aset)) {
                                continue;
                            }

                            $nilai = $penyusun->untuk($aset);
                            fputcsv(
                                $keluaran,
                                $this->netralkan(array_map(fn (string $r): string => $nilai[$r] ?? '', $ruas)),
                                escape: '\\',
                            );
                        }
                    });
            } finally {
                $konteks->bersihkan();
                fclose($keluaran);
            }
        }, 'aspak-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }

    private function profil(): ProfilKolomAspak
    {
        $nilai = KonfigurasiOrganisasi::query()->where('Kunci', self::KUNCI_PROFIL)->value('Nilai');

        return ProfilKolomAspak::dariKonfigurasi(is_string($nilai) ? $nilai : null);
    }

    /**
     * Nama aset dan merek diketik orang, jadi hasil ekspornya dinetralkan
     * seperti ekspor laporan.
     *
     * @param  list<string>  $baris
     * @return list<string>
     */
    private function netralkan(array $baris): array
    {
        return array_map(
            static fn (string $nilai): string => $nilai !== '' && str_contains(self::AWALAN_RUMUS, $nilai[0])
                ? "'".$nilai
                : $nilai,
            $baris,
        );
    }
}
