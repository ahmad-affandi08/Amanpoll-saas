<?php

declare(strict_types=1);

namespace App\Domain\Kodefikasi\Http\Controllers;

use App\Core\Audit\LayananAudit;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Kodefikasi\Application\Actions\ImporKatalogKodeBarang;
use App\Domain\Kodefikasi\Application\Actions\TetapkanKodeBarang;
use App\Domain\Kodefikasi\Application\Services\PenyusunKodeRegistrasi;
use App\Domain\Kodefikasi\Domain\Enums\StandarKodefikasi;
use App\Domain\Kodefikasi\Http\Requests\ImporKatalogKodeBarangRequest;
use App\Domain\Kodefikasi\Http\Requests\TetapkanKodeBarangRequest;
use App\Domain\Kodefikasi\Infrastructure\Persistence\Models\KodeBarang;
use App\Domain\Kodefikasi\Infrastructure\Persistence\Models\KodeBarangAset;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Ekspor\NetralkanRumus;
use App\Shared\Infrastructure\Persistence\BacaRelasi;
use App\Shared\Infrastructure\Persistence\DaftarTersaring;
use App\Shared\Infrastructure\Validasi\AturanWajib;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Kodefikasi barang milik negara dan daerah.
 *
 * SIMAK BMN dan SIMBADA bertukar data lewat berkas seperti ASPAK, tetapi
 * isinya berbeda: yang dilaporkan di sini penatausahaan barang -- kode barang,
 * NUP, nilai perolehan -- bukan kesiapan alat kesehatan per ruangan.
 */
final class KodefikasiController extends Controller
{
    private const MAKS_HASIL_CARI = 50;

    public function __construct(
        private readonly LayananAudit $audit,
        private readonly PenyusunKodeRegistrasi $registrasi,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', KodeBarang::class);

        $standar = $this->standarTerpilih($request);

        $daftar = DaftarTersaring::untuk(
            $request,
            KodeBarang::query()->where('Standar', $standar->value)->withCount('penetapan'),
        )
            ->cari(['Kode', 'Uraian'])
            ->urut(['Kode', 'Uraian'], bawaan: 'Kode');

        return Inertia::render('Kodefikasi/Index', [
            'standar' => $standar->value,
            'daftarStandar' => array_map(
                static fn (StandarKodefikasi $satu): array => ['nilai' => $satu->value, 'label' => $satu->label()],
                StandarKodefikasi::cases(),
            ),
            'kodeBarang' => $daftar->halamanTerpeta(fn (KodeBarang $satu): array => [
                'Id' => $satu->Id,
                'Kode' => $satu->Kode,
                'Uraian' => $satu->Uraian,
                'JumlahAset' => (int) ($satu->penetapan_count ?? 0),
            ]),
            'filter' => $daftar->filterBerlaku(),
            'ringkasan' => $this->ringkasan($standar),
            'wajib' => ['penetapan' => AturanWajib::untuk(TetapkanKodeBarangRequest::class)],
        ]);
    }

    /**
     * Dihitung di basis data supaya angkanya tidak bergantung pada halaman
     * katalog yang sedang tampil.
     *
     * @return array{JumlahKode: int, AsetBerkode: int, AsetBelumBerkode: int}
     */
    private function ringkasan(StandarKodefikasi $standar): array
    {
        $berkode = KodeBarangAset::query()->where('Standar', $standar->value)->count();

        return [
            'JumlahKode' => KodeBarang::query()->where('Standar', $standar->value)->count(),
            'AsetBerkode' => $berkode,
            'AsetBelumBerkode' => Aset::query()->count() - $berkode,
        ];
    }

    /**
     * Pencarian katalog untuk pemilih kode di formulir aset.
     *
     * @return array<int, array{Id: string, Kode: string, Uraian: string}>
     */
    public function cariKatalog(Request $request): array
    {
        $this->authorize('viewAny', KodeBarang::class);

        $standar = $this->standarTerpilih($request);
        $kueri = trim((string) $request->query('q', ''));

        return KodeBarang::query()
            ->where('Standar', $standar->value)
            ->where('Aktif', true)
            ->when($kueri !== '', function ($q) use ($kueri): void {
                $q->where(function ($cari) use ($kueri): void {
                    $cari->where('Kode', 'like', '%'.$kueri.'%')
                        ->orWhere('Uraian', 'like', '%'.$kueri.'%');
                });
            })
            ->orderBy('Kode')
            ->limit(self::MAKS_HASIL_CARI)
            ->get(['Id', 'Kode', 'Uraian'])
            ->map(fn (KodeBarang $satu): array => [
                'Id' => (string) $satu->Id,
                'Kode' => (string) $satu->Kode,
                'Uraian' => (string) $satu->Uraian,
            ])
            ->all();
    }

    public function impor(ImporKatalogKodeBarangRequest $request, ImporKatalogKodeBarang $aksi): RedirectResponse
    {
        $this->authorize('create', KodeBarang::class);

        $standar = StandarKodefikasi::from((string) $request->validated('Standar'));
        $jalur = $request->file('Berkas')?->getRealPath();

        if (! is_string($jalur)) {
            return back()->with('gagal', 'Berkas tidak dapat dibaca.');
        }

        $pegangan = fopen($jalur, 'rb');

        if ($pegangan === false) {
            return back()->with('gagal', 'Berkas tidak dapat dibuka.');
        }

        try {
            $hasil = $aksi->jalankan($pegangan, $standar);
        } finally {
            fclose($pegangan);
        }

        $this->audit->catat('Kodefikasi.KatalogDiimpor', 'KodeBarang', null, dataSesudah: [
            'Standar' => $standar->value,
            ...$hasil,
        ]);

        return back()->with('sukses', sprintf(
            'Impor %s selesai: %d kode baru, %d diperbarui, %d dilewati, %d ditolak karena formatnya tidak sesuai.',
            $standar->label(),
            $hasil['ditambah'],
            $hasil['diperbarui'],
            $hasil['dilewati'],
            $hasil['polaSalah'],
        ));
    }

    public function tetapkan(TetapkanKodeBarangRequest $request, TetapkanKodeBarang $aksi): RedirectResponse
    {
        $this->authorize('create', KodeBarangAset::class);

        $data = $request->validated();
        $aset = Aset::query()->where('Id', $data['AsetId'])->firstOrFail();
        $kodeBarang = KodeBarang::query()->where('Id', $data['KodeBarangId'])->firstOrFail();

        $penetapan = $aksi->jalankan($aset, $kodeBarang);

        return back()->with('sukses', sprintf(
            'Kode %s ditetapkan dengan NUP %06d.',
            $kodeBarang->Kode,
            $penetapan->Nup,
        ));
    }

    /**
     * Kode barang beserta NUP dan kode registrasi satu aset, untuk tab di detailnya.
     *
     * @return list<array{Id: string, Standar: string, LabelStandar: string, Kode: string, Uraian: string, Nup: int, KodeRegistrasi: string|null, AlasanBelumLengkap: string|null}>
     */
    public function untukAset(Aset $aset): array
    {
        $this->authorize('view', $aset);

        $baris = [];

        $penetapan = KodeBarangAset::query()
            ->with(['kodeBarang', 'aset'])
            ->where('AsetId', $aset->Id)
            ->get();

        foreach ($penetapan as $satu) {
            $kode = BacaRelasi::model($satu, 'kodeBarang');

            $baris[] = [
                'Id' => (string) $satu->Id,
                'Standar' => $satu->Standar->value,
                'LabelStandar' => $satu->Standar->label(),
                'Kode' => BacaRelasi::teks($kode, 'Kode'),
                'Uraian' => BacaRelasi::teks($kode, 'Uraian'),
                'Nup' => (int) $satu->Nup,
                'KodeRegistrasi' => $this->registrasi->untuk($satu),
                'AlasanBelumLengkap' => $this->registrasi->alasanBelumLengkap($satu),
            ];
        }

        return $baris;
    }

    /** Daftar aset berkode dalam susunan yang siap diunggah ke sistem penatausahaan. */
    public function ekspor(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', KodeBarang::class);

        $standar = $this->standarTerpilih($request);
        $organisasiId = app(KonteksOrganisasi::class)->wajibId();

        $this->audit->catat('Kodefikasi.Diekspor', 'KodeBarangAset', null, dataSesudah: [
            'Standar' => $standar->value,
        ]);

        return response()->streamDownload(function () use ($standar, $organisasiId): void {
            $keluaran = fopen('php://output', 'wb');

            if ($keluaran === false) {
                return;
            }

            // Isi unduhan dihasilkan sesudah middleware selesai dan konteks
            // organisasinya sudah dibersihkan; tanpa ditetapkan ulang di sini,
            // ScopeOrganisasi menutup seluruh kuerinya.
            $konteks = app(KonteksOrganisasi::class);
            $konteks->tetapkan($organisasiId);

            try {
                fwrite($keluaran, "\xEF\xBB\xBF");
                fputcsv($keluaran, [
                    'Kode Barang', 'Uraian', 'NUP', 'Kode Registrasi',
                    'Kode Aset', 'Nama Aset', 'Nomor Seri', 'Tahun Perolehan', 'Nilai Perolehan', 'Kondisi',
                ], escape: '');

                KodeBarangAset::query()
                    ->with(['kodeBarang', 'aset'])
                    ->where('Standar', $standar->value)
                    ->chunk(300, function ($kumpulan) use ($keluaran): void {
                        foreach ($kumpulan as $satu) {
                            $aset = BacaRelasi::model($satu, 'aset');
                            $kode = BacaRelasi::model($satu, 'kodeBarang');

                            fputcsv($keluaran, NetralkanRumus::barisCsv([
                                BacaRelasi::teks($kode, 'Kode'),
                                BacaRelasi::teks($kode, 'Uraian'),
                                sprintf('%06d', $satu->Nup),
                                (string) ($this->registrasi->untuk($satu) ?? ''),
                                BacaRelasi::teks($aset, 'KodeAset'),
                                BacaRelasi::teks($aset, 'Nama'),
                                BacaRelasi::teks($aset, 'NomorSeri'),
                                $satu->aset?->TanggalPerolehan?->format('Y') ?? '',
                                BacaRelasi::teks($aset, 'HargaPerolehan'),
                                BacaRelasi::teks($aset, 'Kondisi'),
                            ]), escape: '');
                        }
                    });
            } finally {
                $konteks->bersihkan();
                fclose($keluaran);
            }
        }, 'kodefikasi-'.strtolower($standar->value).'-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function standarTerpilih(Request $request): StandarKodefikasi
    {
        return StandarKodefikasi::tryFrom((string) $request->query('standar', ''))
            ?? StandarKodefikasi::SimakBmn;
    }
}
