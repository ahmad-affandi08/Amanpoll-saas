<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Controllers;

use App\Core\Izin\PemeriksaIzin;
use App\Core\Izin\PemeriksaLingkupBaris;
use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Pemeliharaan\Application\Actions\AlihkanUnitPengelolaPerintahKerja;
use App\Domain\Pemeliharaan\Application\Actions\BuatPerintahKerja;
use App\Domain\Pemeliharaan\Application\Actions\UbahStatusPerintahKerja;
use App\Domain\Pemeliharaan\Application\Services\AturanKonfirmasiPenerima;
use App\Domain\Pemeliharaan\Domain\Enums\HasilKonfirmasiPenerima;
use App\Domain\Pemeliharaan\Domain\Enums\PrioritasKeluhan;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Pemeliharaan\Http\Requests\AksiWaktuHentiAsetRequest;
use App\Domain\Pemeliharaan\Http\Requests\AlihkanUnitPengelolaPerintahKerjaRequest;
use App\Domain\Pemeliharaan\Http\Requests\SimpanAnalisisKegagalanRequest;
use App\Domain\Pemeliharaan\Http\Requests\SimpanBiayaPerintahKerjaRequest;
use App\Domain\Pemeliharaan\Http\Requests\SimpanPenugasanPerintahKerjaRequest;
use App\Domain\Pemeliharaan\Http\Requests\SimpanPerintahKerjaRequest;
use App\Domain\Pemeliharaan\Http\Requests\UbahStatusPerintahKerjaRequest;
use App\Domain\Pemeliharaan\Http\Resources\KonfirmasiPenerimaResource;
use App\Domain\Pemeliharaan\Http\Resources\PerintahKerjaResource;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KodeKegagalan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KonfirmasiPenerimaPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;
use App\Domain\Persediaan\Application\Services\LingkupGudang;
use App\Domain\Persediaan\Http\Requests\SimpanReservasiSukuCadangRequest;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\StokSukuCadang;
use App\Domain\Platform\Application\Services\OpsiUnitPengelola;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Ekspor\EksporDaftar;
use App\Shared\Infrastructure\Ekspor\KolomEkspor;
use App\Shared\Infrastructure\Persistence\BacaRelasi;
use App\Shared\Infrastructure\Persistence\BatasDaftar;
use App\Shared\Infrastructure\Validasi\AturanWajib;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PerintahKerjaController extends Controller
{
    public function __construct(private readonly PemeriksaIzin $izin) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PerintahKerja::class);
        $filter = $request->validate([
            'status' => ['nullable', Rule::enum(StatusPerintahKerja::class)],
            'prioritas' => ['nullable', Rule::enum(PrioritasKeluhan::class)],
            'unitPengelola' => ['nullable', 'string', 'size:26'],
        ]);
        $dapatMengelola = $this->izin->boleh($request->user('web')->Id, 'PerintahKerja.Kelola');

        $daftar = $this->kueriTersaring($request, $filter)->paginate(25)->withQueryString();

        return Inertia::render('PerintahKerja/Index', [
            'wajib' => ['perintahKerja' => AturanWajib::untuk(SimpanPerintahKerjaRequest::class), 'status' => AturanWajib::untuk(UbahStatusPerintahKerjaRequest::class)],
            'perintahKerja' => PerintahKerjaResource::collection($daftar),
            'keluhan' => Keluhan::query()->whereIn('Status', ['Diterima', 'Diproses'])->latest('DilaporkanPada')->get(['Id', 'Nomor', 'Judul', 'Prioritas', 'LokasiId', 'AsetId', 'UnitPengelolaId']),
            'aset' => Aset::query()->where('Status', StatusAset::Aktif->value)->orderBy('Nama')->get(['Id', 'KodeAset', 'Nama', 'LokasiId', 'UnitPengelolaId']),
            'lokasi' => Lokasi::query()->where('Status', 'Aktif')->orderBy('Nama')->get(['Id', 'Nama']),
            'filter' => $filter,
            'dapatMengelola' => $dapatMengelola,
            'unitPengelolaDipakai' => OpsiUnitPengelola::dipakai(),
            'pilihanUnitPengelola' => OpsiUnitPengelola::daftar(),
            'saringanUnitPengelola' => OpsiUnitPengelola::daftar(termasukNonaktif: true),
        ]);
    }

    /**
     * Penyaring daftar perintah kerja, dipakai bersama halaman dan ekspornya.
     *
     * Pembatasan "hanya yang ditugaskan kepada saya" bagi yang tidak memegang
     * PerintahKerja.Kelola ikut di sini: ekspor yang melewatinya akan
     * menyerahkan seluruh pekerjaan organisasi kepada satu teknisi.
     *
     * @param  array<string, mixed>  $filter
     * @return Builder<PerintahKerja>
     */
    private function kueriTersaring(Request $request, array $filter): Builder
    {
        $dapatMengelola = $this->izin->boleh($request->user('web')->Id, 'PerintahKerja.Kelola');

        return PerintahKerja::query()
            ->with(['keluhan', 'lokasi', 'aset', 'penugasan.pengguna', 'unitPengelola:Id,Kode,Nama'])
            ->withSum('waktuKerja as TotalWaktuKerjaMenit', 'DurasiMenit')
            ->withSum('waktuHenti as TotalDowntimeMenit', 'DurasiMenit')
            ->withSum('biaya as TotalBiaya', 'Jumlah')
            ->withExists(['konfirmasiPenerima as SudahDikonfirmasiPenerima' => fn ($konfirmasi) => $konfirmasi
                ->where('Berlaku', true)
                ->where('Hasil', HasilKonfirmasiPenerima::Diterima->value)])
            ->when(! $dapatMengelola, fn ($query) => $query->whereHas('penugasan', fn ($penugasan) => $penugasan->where('PenggunaId', $request->user('web')->Id)->whereIn('Status', ['Ditugaskan', 'Diterima'])))
            ->when($filter['status'] ?? null, fn ($query, $status) => $query->where('Status', $status))
            ->when($filter['prioritas'] ?? null, fn ($query, $prioritas) => $query->where('Prioritas', $prioritas))
            ->when($filter['unitPengelola'] ?? null, fn ($query, $unitPengelolaId) => $query->where('UnitPengelolaId', $unitPengelolaId))
            ->latest('DibuatPada')
            ->orderBy('Id');
    }

    /** Daftar perintah kerja seperti yang tampil di layar, lengkap dengan penyaringnya. */
    public function ekspor(Request $request, EksporDaftar $ekspor): StreamedResponse
    {
        $this->authorize('viewAny', PerintahKerja::class);

        $filter = $request->validate([
            'status' => ['nullable', Rule::enum(StatusPerintahKerja::class)],
            'prioritas' => ['nullable', Rule::enum(PrioritasKeluhan::class)],
            'unitPengelola' => ['nullable', 'string', 'size:26'],
        ]);

        return $ekspor->unduh(
            $this->kueriTersaring($request, $filter),
            [
                KolomEkspor::atribut('Nomor', 'Nomor'),
                KolomEkspor::atribut('Judul', 'Judul'),
                KolomEkspor::atribut('Jenis', 'Jenis'),
                KolomEkspor::atribut('Prioritas', 'Prioritas'),
                KolomEkspor::atribut('Status', 'Status'),
                KolomEkspor::dari('Nomor Keluhan', fn (PerintahKerja $p): string => BacaRelasi::teks(BacaRelasi::model($p, 'keluhan'), 'Nomor')),
                KolomEkspor::dari('Aset', fn (PerintahKerja $p): string => BacaRelasi::teks(BacaRelasi::model($p, 'aset'), 'Nama')),
                KolomEkspor::dari('Kode Aset', fn (PerintahKerja $p): string => BacaRelasi::teks(BacaRelasi::model($p, 'aset'), 'KodeAset')),
                KolomEkspor::dari('Lokasi', fn (PerintahKerja $p): string => BacaRelasi::teks(BacaRelasi::model($p, 'lokasi'), 'Nama')),
                ...(OpsiUnitPengelola::dipakai()
                    ? [KolomEkspor::dari('Unit Pengelola', fn (PerintahKerja $p): string => BacaRelasi::teks(BacaRelasi::model($p, 'unitPengelola'), 'Nama'))]
                    : []),
                KolomEkspor::dari('Teknisi', fn (PerintahKerja $p): string => $p->penugasan
                    ->map(fn (PenugasanPerintahKerja $satu): string => BacaRelasi::teks(BacaRelasi::model($satu, 'pengguna'), 'Nama'))
                    ->filter()
                    ->implode(', ')),
                KolomEkspor::tanggal('Dijadwalkan Mulai', 'DijadwalkanMulaiPada', 'Y-m-d H:i'),
                KolomEkspor::tanggal('Dimulai', 'DimulaiPada', 'Y-m-d H:i'),
                KolomEkspor::tanggal('Diselesaikan', 'DiselesaikanPada', 'Y-m-d H:i'),
                KolomEkspor::atribut('Persentase Selesai', 'PersentaseSelesai'),
                KolomEkspor::atribut('Total Waktu Kerja (menit)', 'TotalWaktuKerjaMenit'),
                KolomEkspor::atribut('Total Downtime (menit)', 'TotalDowntimeMenit'),
                KolomEkspor::atribut('Total Biaya', 'TotalBiaya'),
            ],
            'daftar-perintah-kerja',
            EksporDaftar::formatDari($request),
        );
    }

    public function store(SimpanPerintahKerjaRequest $request, BuatPerintahKerja $aksi): RedirectResponse
    {
        $this->authorize('create', PerintahKerja::class);
        $perintahKerja = $aksi->jalankan($request->validated(), $request->user('web')->Id);

        return redirect()->route('pemeliharaan.perintah-kerja.show', $perintahKerja)->with('sukses', 'Perintah kerja berhasil dibuat.');
    }

    public function show(Request $request, PerintahKerja $perintahKerja): Response
    {
        $this->authorize('view', $perintahKerja);
        $perintahKerja->load([
            'keluhan', 'lokasi', 'aset', 'penugasan.pengguna', 'riwayatStatus.diubahOleh', 'unitPengelola:Id,Kode,Nama',
            'waktuKerja.pengguna', 'waktuHenti.aset', 'biaya',
            'analisisKegagalan.kodeMasalah', 'analisisKegagalan.kodePenyebab', 'analisisKegagalan.kodeTindakan',
            'reservasiSukuCadang.sukuCadang', 'reservasiSukuCadang.gudang', 'pemakaianSukuCadang.sukuCadang',
        ])->loadSum('waktuKerja as TotalWaktuKerjaMenit', 'DurasiMenit')
            ->loadSum('waktuHenti as TotalDowntimeMenit', 'DurasiMenit')
            ->loadSum('biaya as TotalBiaya', 'Jumlah')
            ->loadExists(['konfirmasiPenerima as SudahDikonfirmasiPenerima' => fn ($konfirmasi) => $konfirmasi
                ->where('Berlaku', true)
                ->where('Hasil', HasilKonfirmasiPenerima::Diterima->value)]);
        $aturanKonfirmasi = app(AturanKonfirmasiPenerima::class);

        $dapatMengelola = $this->izin->boleh($request->user('web')->Id, 'PerintahKerja.Kelola');
        $beban = PenugasanPerintahKerja::query()
            ->whereIn('Status', ['Ditugaskan', 'Diterima'])
            ->selectRaw('PenggunaId, COUNT(*) as jumlah')
            ->groupBy('PenggunaId')
            ->pluck('jumlah', 'PenggunaId');
        $calonTeknisi = Pengguna::query()->where('OrganisasiId', $request->user('web')->OrganisasiId)->where('Status', 'Aktif')->orderBy('Nama')->get(['Id', 'Nama', 'Jabatan']);
        $idTercakup = array_flip(app(PemeriksaLingkupBaris::class)->penggunaYangMencakup($perintahKerja, $calonTeknisi->pluck('Id')->all()));
        $teknisi = $calonTeknisi
            ->filter(fn (Pengguna $pengguna): bool => isset($idTercakup[$pengguna->Id]))
            ->values()
            ->map(fn (Pengguna $pengguna) => [
                'Id' => $pengguna->Id,
                'Nama' => $pengguna->Nama,
                'Jabatan' => $pengguna->Jabatan,
                'BebanAktif' => (int) ($beban[$pengguna->Id] ?? 0),
            ]);
        // Hanya stok di gudang yang terlihat pengguna (PRD 8.21), sama dengan yang diterima GudangTerlihat saat reservasi.
        $stok = app(LingkupGudang::class)->saring(StokSukuCadang::query(), 'StokSukuCadang.GudangId')
            ->with(['sukuCadang', 'gudang'])
            ->whereColumn('JumlahTersedia', '>', 'JumlahDitahan')
            ->limit(BatasDaftar::MAKS)
            ->get()
            ->groupBy(fn (StokSukuCadang $item): string => "{$item->GudangId}:{$item->SukuCadangId}")
            ->map(fn ($baris) => [
                'GudangId' => $baris->first()->GudangId,
                'NamaGudang' => $baris->first()->gudang?->Nama,
                'SukuCadangId' => $baris->first()->SukuCadangId,
                'NamaSukuCadang' => $baris->first()->sukuCadang?->Nama,
                'KodeSukuCadang' => $baris->first()->sukuCadang?->Kode,
                'TersediaBersih' => $baris->sum(fn (StokSukuCadang $item): float => $item->jumlahTersediaBersih()),
            ])->values();

        return Inertia::render('PerintahKerja/Show', [
            'wajib' => ['perintahKerja' => AturanWajib::untuk(SimpanPerintahKerjaRequest::class), 'status' => AturanWajib::untuk(UbahStatusPerintahKerjaRequest::class), 'penugasan' => AturanWajib::untuk(SimpanPenugasanPerintahKerjaRequest::class), 'biaya' => AturanWajib::untuk(SimpanBiayaPerintahKerjaRequest::class), 'analisis' => AturanWajib::untuk(SimpanAnalisisKegagalanRequest::class), 'waktuHenti' => AturanWajib::untuk(AksiWaktuHentiAsetRequest::class), 'reservasi' => AturanWajib::untuk(SimpanReservasiSukuCadangRequest::class), 'unitPengelola' => AturanWajib::untuk(AlihkanUnitPengelolaPerintahKerjaRequest::class)],
            'perintahKerja' => new PerintahKerjaResource($perintahKerja),
            'dapatMengelola' => $dapatMengelola,
            'dapatMengoperasikan' => Gate::allows('operate', $perintahKerja),
            'transisiDiizinkan' => collect(StatusPerintahKerja::from($perintahKerja->Status)->tujuanYangDiizinkan())
                ->filter(fn (StatusPerintahKerja $status): bool => Gate::allows('ubahStatus', [$perintahKerja, $status->value]))
                ->map(fn (StatusPerintahKerja $status): string => $status->value)->values(),
            'penugasanSaya' => $perintahKerja->penugasan->firstWhere('PenggunaId', $request->user('web')->Id),
            // Kartu "Konfirmasi penerima" (PRD 8.22): seluruh jawaban, terbaru dulu, dan alasan verifikasi dikunci.
            'konfirmasiPenerima' => $perintahKerja->konfirmasiPenerima()
                ->latest('DikonfirmasiPada')
                ->orderByDesc('Id')
                ->get()
                ->map(fn (KonfirmasiPenerimaPerintahKerja $konfirmasi): array => KonfirmasiPenerimaResource::ringkas($konfirmasi))
                ->values(),
            'konfirmasiWajib' => $aturanKonfirmasi->wajib($perintahKerja->OrganisasiId),
            'alasanVerifikasiDiblokir' => $aturanKonfirmasi->alasanVerifikasiDiblokir($perintahKerja),
            'teknisi' => $teknisi,
            'jumlahTeknisiDiluarLingkup' => $calonTeknisi->count() - $teknisi->count(),
            'unitPengelolaDipakai' => OpsiUnitPengelola::dipakai(),
            'pilihanUnitPengelola' => OpsiUnitPengelola::daftar(),
            'stok' => $stok,
            'gudang' => Gudang::query()->orderBy('Nama')->get(['Id', 'Nama']),
            'penyedia' => Penyedia::query()->where('Status', 'Aktif')->orderBy('Nama')->get(['Id', 'Nama']),
            'kodeKegagalan' => KodeKegagalan::query()->where('Aktif', true)->orderBy('Jenis')->orderBy('Kode')->get(['Id', 'Kode', 'Nama', 'Jenis']),
        ]);
    }

    public function alihkanUnitPengelola(AlihkanUnitPengelolaPerintahKerjaRequest $request, PerintahKerja $perintahKerja, AlihkanUnitPengelolaPerintahKerja $aksi): RedirectResponse
    {
        $this->authorize('alihkanUnitPengelola', $perintahKerja);
        $tujuan = $request->validated('UnitPengelolaId');
        $aksi->jalankan($perintahKerja, is_string($tujuan) ? $tujuan : null, $request->string('Alasan')->toString());

        return back()->with('sukses', 'Unit pengelola perintah kerja berhasil dialihkan.');
    }

    public function ubahStatus(UbahStatusPerintahKerjaRequest $request, PerintahKerja $perintahKerja, UbahStatusPerintahKerja $aksi): RedirectResponse
    {
        $this->authorize('ubahStatus', [$perintahKerja, $request->validated('Status')]);
        $aksi->jalankan(
            $perintahKerja,
            StatusPerintahKerja::from($request->validated('Status')),
            $request->validated('Catatan'),
            $request->validated('RingkasanPenyelesaian'),
            $request->integer('Versi'),
            $request->user('web')->Id,
        );

        return back()->with('sukses', 'Status perintah kerja berhasil diperbarui.');
    }
}
