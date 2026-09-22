<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Controllers;

use App\Domain\Pemasaran\Application\Services\LayananOtomasiPemasaran;
use App\Domain\Pemasaran\Application\Services\RegistriTindakanOtomasi;
use App\Domain\Pemasaran\Domain\Enums\JenisLangkahOtomasi;
use App\Domain\Pemasaran\Domain\Enums\StatusEksekusiOtomasi;
use App\Domain\Pemasaran\Domain\KatalogKondisiOtomasi;
use App\Domain\Pemasaran\Domain\KatalogPemicuOtomasi;
use App\Domain\Pemasaran\Http\Requests\SimpanOtomasiPemasaranRequest;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\EksekusiOtomasiPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\LangkahOtomasiPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\LogEksekusiOtomasi;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\OtomasiPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\VersiOtomasiPemasaran;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Konsol otomasi pemasaran (MARKETING.md 17). */
final class OtomasiPemasaranController extends Controller
{
    public function __construct(
        private readonly LayananOtomasiPemasaran $layanan,
        private readonly RegistriTindakanOtomasi $tindakan,
    ) {}

    public function index(): Response
    {
        $otomasi = OtomasiPemasaran::query()
            ->with('versiAktif')
            ->withCount([
                'eksekusi as jumlah_berjalan' => fn ($kueri) => $kueri
                    ->whereIn('Status', [
                        StatusEksekusiOtomasi::Berjalan->value,
                        StatusEksekusiOtomasi::Tertunda->value,
                        StatusEksekusiOtomasi::Gagal->value,
                    ]),
                'eksekusi as jumlah_dlq' => fn ($kueri) => $kueri
                    ->where('Status', StatusEksekusiOtomasi::GagalPermanen->value),
            ])
            ->orderBy('Kode')
            ->get();

        return Inertia::render('Pemasaran/Otomasi/Index', [
            'otomasi' => $otomasi->map(fn (OtomasiPemasaran $satu): array => [
                'Id' => $satu->Id,
                'Kode' => $satu->Kode,
                'Nama' => $satu->Nama,
                'Keterangan' => $satu->Keterangan,
                'Pemicu' => $satu->Pemicu,
                'PemicuBerlaku' => KatalogPemicuOtomasi::berlaku((string) $satu->Pemicu),
                'Aktif' => $satu->Aktif,
                'NomorVersiAktif' => $satu->versiAktif?->Nomor,
                'JumlahBerjalan' => (int) ($satu->jumlah_berjalan ?? 0),
                'JumlahDlq' => (int) ($satu->jumlah_dlq ?? 0),
            ])->all(),
            'pilihan' => ['Pemicu' => KatalogPemicuOtomasi::semua()],
        ]);
    }

    public function show(OtomasiPemasaran $otomasi): Response
    {
        $otomasi->load(['versi.langkah']);

        return Inertia::render('Pemasaran/Otomasi/Show', [
            'otomasi' => [
                'Id' => $otomasi->Id,
                'Kode' => $otomasi->Kode,
                'Nama' => $otomasi->Nama,
                'Keterangan' => $otomasi->Keterangan,
                'Pemicu' => $otomasi->Pemicu,
                'PemicuBerlaku' => KatalogPemicuOtomasi::berlaku((string) $otomasi->Pemicu),
                'Aktif' => $otomasi->Aktif,
                'VersiAktifId' => $otomasi->VersiAktifId,
            ],
            'versi' => $otomasi->versi->map(fn (VersiOtomasiPemasaran $satu): array => [
                'Id' => $satu->Id,
                'Nomor' => $satu->Nomor,
                'Status' => $satu->Status->value,
                'DiterbitkanPada' => $satu->DiterbitkanPada?->toIso8601String(),
                'Langkah' => array_values($satu->langkah
                    ->map(fn (LangkahOtomasiPemasaran $langkah): array => [
                        'Id' => $langkah->Id,
                        'Urutan' => $langkah->Urutan,
                        'Jenis' => $langkah->Jenis->value,
                        'Konfigurasi' => $langkah->Konfigurasi,
                    ])->all()),
            ])->all(),
            'eksekusi' => $this->eksekusiTerakhir($otomasi),
            'pilihan' => [
                'Jenis' => array_column(JenisLangkahOtomasi::cases(), 'value'),
                'Aksi' => $this->tindakan->label(),
                'Bidang' => array_combine(
                    KatalogKondisiOtomasi::kode(),
                    array_map(KatalogKondisiOtomasi::operator(...), KatalogKondisiOtomasi::kode()),
                ),
            ],
        ]);
    }

    public function store(SimpanOtomasiPemasaranRequest $request): RedirectResponse
    {
        $otomasi = $this->layanan->simpan(null, $this->data($request));

        return to_route('pemasaran.otomasi.show', $otomasi->Kode)
            ->with('sukses', 'Otomasi berhasil dibuat.');
    }

    public function update(
        SimpanOtomasiPemasaranRequest $request,
        OtomasiPemasaran $otomasi,
    ): RedirectResponse {
        $this->layanan->simpan($otomasi, $this->data($request));

        return back()->with('sukses', 'Otomasi berhasil diperbarui.');
    }

    public function buatVersi(OtomasiPemasaran $otomasi): RedirectResponse
    {
        $this->layanan->buatVersi($otomasi, salinVersiAktif: true);

        return back()->with('sukses', 'Draf versi baru dibuat dari versi aktif.');
    }

    public function simpanLangkah(
        Request $request,
        OtomasiPemasaran $otomasi,
        VersiOtomasiPemasaran $versi,
        ?LangkahOtomasiPemasaran $langkah = null,
    ): RedirectResponse {
        /** @var array{Jenis: string, Urutan: int, Konfigurasi: array<string, mixed>} $sah */
        $sah = $request->validate([
            'Jenis' => ['required', 'string'],
            'Urutan' => ['required', 'integer', 'between:0,99'],
            'Konfigurasi' => ['required', 'array'],
        ]);

        $this->layanan->simpanLangkah($versi, $langkah, $sah);

        return back()->with('sukses', 'Langkah otomasi berhasil disimpan.');
    }

    public function hapusLangkah(
        OtomasiPemasaran $otomasi,
        VersiOtomasiPemasaran $versi,
        LangkahOtomasiPemasaran $langkah,
    ): RedirectResponse {
        $this->layanan->hapusLangkah($versi, $langkah);

        return back()->with('sukses', 'Langkah otomasi berhasil dihapus.');
    }

    public function aktifkanVersi(
        Request $request,
        OtomasiPemasaran $otomasi,
        VersiOtomasiPemasaran $versi,
    ): RedirectResponse {
        $this->layanan->aktifkan($versi, $request->user('platform')?->getAuthIdentifier());

        return back()->with('sukses', "Versi {$versi->Nomor} diaktifkan.");
    }

    public function ubahAktif(Request $request, OtomasiPemasaran $otomasi): RedirectResponse
    {
        /** @var array{Aktif: bool} $sah */
        $sah = $request->validate(['Aktif' => ['required', 'boolean']]);

        $this->layanan->ubahAktif($otomasi, $sah['Aktif']);

        return back()->with('sukses', $sah['Aktif'] ? 'Otomasi dinyalakan.' : 'Otomasi dimatikan.');
    }

    /** @return list<array<string, mixed>> */
    private function eksekusiTerakhir(OtomasiPemasaran $otomasi): array
    {
        $eksekusi = EksekusiOtomasiPemasaran::query()
            ->with('prospek')
            ->where('OtomasiPemasaranId', $otomasi->Id)
            ->orderByDesc('DimulaiPada')
            ->limit(50)
            ->get();

        return array_values($eksekusi->map(fn (EksekusiOtomasiPemasaran $satu): array => [
            'Id' => $satu->Id,
            'Prospek' => $satu->prospek?->Nama,
            'Status' => $satu->Status->value,
            'LangkahBerikutnya' => $satu->LangkahBerikutnya,
            'Percobaan' => $satu->Percobaan,
            'LanjutPada' => $satu->LanjutPada?->toIso8601String(),
            'Galat' => $satu->Galat,
            'DimulaiPada' => $satu->DimulaiPada->toIso8601String(),
            'Log' => array_values($satu->log->map(fn (LogEksekusiOtomasi $log): array => [
                'Urutan' => $log->Urutan,
                'Jenis' => $log->Jenis->value,
                'Hasil' => $log->Hasil->value,
                'Ringkasan' => $log->Ringkasan,
            ])->all()),
        ])->all());
    }

    /** @return array{Kode: string, Nama: string, Keterangan: string|null, Pemicu: string} */
    private function data(SimpanOtomasiPemasaranRequest $request): array
    {
        /** @var array{Kode: string, Nama: string, Keterangan?: string|null, Pemicu: string} $sah */
        $sah = $request->validated();

        return [
            'Kode' => $sah['Kode'],
            'Nama' => $sah['Nama'],
            'Keterangan' => $sah['Keterangan'] ?? null,
            'Pemicu' => $sah['Pemicu'],
        ];
    }
}
