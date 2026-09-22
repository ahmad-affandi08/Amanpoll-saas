<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Controllers;

use App\Core\Host\PetaHost;
use App\Domain\Langganan\Domain\KatalogFitur;
use App\Domain\Pemasaran\Application\Actions\KembalikanVersiHalaman;
use App\Domain\Pemasaran\Application\Actions\SimpanDrafHalaman;
use App\Domain\Pemasaran\Application\Actions\TerbitkanHalaman;
use App\Domain\Pemasaran\Application\Actions\UbahStatusHalaman;
use App\Domain\Pemasaran\Application\Services\PenyusunPresentasiHarga;
use App\Domain\Pemasaran\Domain\Enums\JenisBlokHalaman;
use App\Domain\Pemasaran\Domain\Enums\SiklusHarga;
use App\Domain\Pemasaran\Domain\Enums\StatusHalamanPemasaran;
use App\Domain\Pemasaran\Domain\Enums\TipeHalamanPemasaran;
use App\Domain\Pemasaran\Domain\KatalogSegmenHalaman;
use App\Domain\Pemasaran\Http\Requests\SimpanHalamanPemasaranRequest;
use App\Domain\Pemasaran\Http\Requests\UbahStatusHalamanRequest;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\BlokHalamanPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\FormulirPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\HalamanPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Kampanye;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\VersiHalamanPemasaran;
use App\Http\Controllers\Controller;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Infrastructure\Persistence\DaftarTersaring;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

/** Landing page builder di konsol platform (MARKETING.md 8). */
final class HalamanPemasaranController extends Controller
{
    /** Umur tautan pratinjau. Cukup untuk ditinjau, terlalu pendek untuk beredar. */
    private const PRATINJAU_MENIT = 60;

    public function __construct(
        private readonly PetaHost $host,
        private readonly PenyusunPresentasiHarga $harga,
    ) {}

    public function index(Request $request): Response
    {
        $daftar = DaftarTersaring::untuk(
            $request,
            HalamanPemasaran::query()->with(['versiTerbit', 'versiDraf', 'kampanye'])->withCount('versi'),
        )
            ->cari(['Slug', 'Judul'])
            ->urut(['Judul', 'Slug', 'Tipe', 'Status', 'DiperbaruiPada'], bawaan: 'DiperbaruiPada', arahBawaan: 'desc')
            ->faset(['Status', 'Tipe']);

        return Inertia::render('Pemasaran/Halaman/Index', [
            'halaman' => $daftar->halamanTerpeta(fn (HalamanPemasaran $satu): array => $this->ringkas($satu)),
            'filter' => $daftar->filterBerlaku(),
            'pilihan' => $this->pilihan(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Pemasaran/Halaman/Editor', [
            'halaman' => null,
            'versi' => [],
            'pilihan' => $this->pilihan(),
        ]);
    }

    public function edit(HalamanPemasaran $halaman): Response
    {
        $halaman->load(['versiDraf.blok.formulir', 'versiTerbit']);

        $disunting = $halaman->versiDraf ?? $halaman->versiTerbit;

        return Inertia::render('Pemasaran/Halaman/Editor', [
            'halaman' => [
                ...$this->ringkas($halaman),
                ...$this->isiVersi($disunting),
            ],
            'versi' => $this->riwayat($halaman),
            'pilihan' => $this->pilihan(),
        ]);
    }

    public function store(SimpanHalamanPemasaranRequest $request, SimpanDrafHalaman $aksi): RedirectResponse
    {
        $halaman = $aksi->jalankan(null, $request->validated());

        return redirect()
            ->route('pemasaran.halaman.edit', $halaman->Id)
            ->with('sukses', 'Halaman berhasil dibuat sebagai draf.');
    }

    public function update(
        SimpanHalamanPemasaranRequest $request,
        HalamanPemasaran $halaman,
        SimpanDrafHalaman $aksi,
    ): RedirectResponse {
        $aksi->jalankan($halaman, $request->validated());

        return back()->with('sukses', 'Draf baru berhasil disimpan.');
    }

    public function terbitkan(HalamanPemasaran $halaman, TerbitkanHalaman $aksi): RedirectResponse
    {
        $aksi->jalankan($halaman);

        return back()->with('sukses', 'Halaman berhasil diterbitkan.');
    }

    public function ubahStatus(
        UbahStatusHalamanRequest $request,
        HalamanPemasaran $halaman,
        UbahStatusHalaman $aksi,
    ): RedirectResponse {
        $data = $request->validated();

        $aksi->jalankan(
            $halaman,
            StatusHalamanPemasaran::from($data['Status']),
            $this->waktu($data['TerbitPada'] ?? null),
            $this->waktu($data['TarikPada'] ?? null),
        );

        return back()->with('sukses', 'Status halaman berhasil diperbarui.');
    }

    public function kembalikan(
        HalamanPemasaran $halaman,
        VersiHalamanPemasaran $versi,
        KembalikanVersiHalaman $aksi,
    ): RedirectResponse {
        $aksi->jalankan($halaman, $versi);

        return back()->with('sukses', "Halaman berhasil dikembalikan ke versi {$versi->Nomor}.");
    }

    /** Tautan pratinjau dibuat di sisi server dan berumur pendek. */
    public function pratinjau(HalamanPemasaran $halaman, VersiHalamanPemasaran $versi): RedirectResponse
    {
        if ($versi->HalamanPemasaranId !== $halaman->Id) {
            throw new AturanBisnisDilanggar('Versi tersebut bukan milik halaman ini.');
        }

        if (! $this->host->situsPublikAktif()) {
            throw new AturanBisnisDilanggar('Situs publik belum dikonfigurasi, pratinjau tidak tersedia.');
        }

        return redirect()->away(URL::temporarySignedRoute(
            'publik.pratinjau',
            now()->addMinutes(self::PRATINJAU_MENIT),
            ['halaman' => $halaman->Id, 'versi' => $versi->Id],
        ));
    }

    private function waktu(?string $nilai): ?CarbonImmutable
    {
        return $nilai === null ? null : CarbonImmutable::parse($nilai);
    }

    /** @return array<string, mixed> */
    private function ringkas(HalamanPemasaran $halaman): array
    {
        return [
            'Id' => $halaman->Id,
            'Slug' => $halaman->Slug,
            'Tipe' => $halaman->Tipe,
            'Judul' => $halaman->Judul,
            'Status' => $halaman->Status->value,
            'Segmen' => $halaman->Segmen,
            'KampanyeId' => $halaman->KampanyeId,
            'Kampanye' => $halaman->kampanye?->Nama,
            'NoIndex' => $halaman->NoIndex,
            'TerbitPada' => $halaman->TerbitPada?->toIso8601String(),
            'TarikPada' => $halaman->TarikPada?->toIso8601String(),
            'VersiTerbitNomor' => $halaman->versiTerbit?->Nomor,
            'VersiDrafNomor' => $halaman->versiDraf?->Nomor,
            'VersiDrafId' => $halaman->VersiDrafId,
            'UrlPublik' => $this->host->urlKanonik($halaman->Slug),
        ];
    }

    /** @return array<string, mixed> */
    private function isiVersi(?VersiHalamanPemasaran $versi): array
    {
        if ($versi === null) {
            return ['Blok' => []];
        }

        $versi->loadMissing('blok.formulir');

        return [
            'MetaJudul' => $versi->MetaJudul,
            'MetaDeskripsi' => $versi->MetaDeskripsi,
            'Kanonik' => $versi->Kanonik,
            'OgJudul' => $versi->OgJudul,
            'OgDeskripsi' => $versi->OgDeskripsi,
            'OgGambar' => $versi->OgGambar,
            'SkemaTipe' => $versi->SkemaTipe,
            'Blok' => $versi->blok
                ->map(fn (BlokHalamanPemasaran $blok): array => [
                    'Jenis' => $blok->Jenis->value,
                    'Isi' => $blok->Isi ?? [],
                    'FormulirKode' => $blok->formulir?->Kode,
                ])
                ->values()
                ->all(),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function riwayat(HalamanPemasaran $halaman): array
    {
        return array_values(VersiHalamanPemasaran::query()
            ->where('HalamanPemasaranId', $halaman->Id)
            ->orderByDesc('Nomor')
            ->limit(50)
            ->get()
            ->map(fn (VersiHalamanPemasaran $versi): array => [
                'Id' => $versi->Id,
                'Nomor' => $versi->Nomor,
                'Judul' => $versi->Judul,
                'Catatan' => $versi->Catatan,
                'DibuatPada' => $versi->DibuatPada->toIso8601String(),
                'Terbit' => $versi->Id === $halaman->VersiTerbitId,
                'Draf' => $versi->Id === $halaman->VersiDrafId,
            ])
            ->all());
    }

    /** @return array<string, mixed> */
    private function pilihan(): array
    {
        return [
            'Tipe' => array_column(TipeHalamanPemasaran::cases(), 'value'),
            'Status' => array_column(StatusHalamanPemasaran::cases(), 'value'),
            'Blok' => array_column(JenisBlokHalaman::cases(), 'value'),
            // Blok harga menyebut kode paket, jadi editor tidak perlu menebaknya.
            'Paket' => $this->harga->paketTersedia(),
            'SiklusHarga' => array_column(SiklusHarga::cases(), 'value'),
            'FiturPaket' => KatalogFitur::kode(),
            'Segmen' => KatalogSegmenHalaman::semua(),
            'Formulir' => FormulirPemasaran::query()
                ->orderBy('Nama')
                ->get()
                ->map(fn (FormulirPemasaran $satu): array => ['Kode' => $satu->Kode, 'Nama' => $satu->Nama])
                ->values()
                ->all(),
            'Kampanye' => Kampanye::query()
                ->orderBy('Nama')
                ->get()
                ->map(fn (Kampanye $satu): array => ['Id' => $satu->Id, 'Nama' => $satu->Nama])
                ->values()
                ->all(),
        ];
    }
}
