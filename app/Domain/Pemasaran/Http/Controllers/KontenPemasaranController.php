<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Controllers;

use App\Core\Audit\LayananAudit;
use App\Core\Host\PetaHost;
use App\Domain\Pemasaran\Application\Actions\SimpanDrafKonten;
use App\Domain\Pemasaran\Application\Actions\TerbitkanKonten;
use App\Domain\Pemasaran\Application\Actions\UbahStatusKonten;
use App\Domain\Pemasaran\Domain\Enums\IntentKeyword;
use App\Domain\Pemasaran\Domain\Enums\JenisKontenPemasaran;
use App\Domain\Pemasaran\Domain\Enums\PrioritasKeyword;
use App\Domain\Pemasaran\Domain\Enums\StatusHalamanPemasaran;
use App\Domain\Pemasaran\Domain\Enums\StatusKeywordSeo;
use App\Domain\Pemasaran\Http\Requests\SimpanKeywordSeoRequest;
use App\Domain\Pemasaran\Http\Requests\SimpanKontenPemasaranRequest;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\ClusterSeo;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Kampanye;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KeywordSeo;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KontenKeywordSeo;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KontenPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\VersiKontenPemasaran;
use App\Http\Controllers\Controller;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Infrastructure\Validasi\AturanWajib;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Konsol CMS konten beserta keyword managernya (MARKETING.md 9). */
final class KontenPemasaranController extends Controller
{
    public function __construct(
        private readonly PetaHost $host,
        private readonly LayananAudit $audit,
    ) {}

    public function index(): Response
    {
        $konten = KontenPemasaran::query()
            ->with(['versiTerbit', 'versiDraf', 'kampanye:Id,Kode,Nama', 'tautanKeyword.keyword:Id,Keyword'])
            ->orderByDesc('DiperbaruiPada')
            ->get();

        return Inertia::render('Pemasaran/Konten', [
            'wajib' => ['konten' => AturanWajib::untuk(SimpanKontenPemasaranRequest::class), 'keyword' => AturanWajib::untuk(SimpanKeywordSeoRequest::class)],
            'konten' => $konten->map(fn (KontenPemasaran $satu): array => $this->ringkas($satu))->all(),
            'keyword' => $this->daftarKeyword(),
            'cluster' => ClusterSeo::query()
                ->orderBy('Nama')
                ->get(['Id', 'Kode', 'Nama', 'Keterangan'])
                ->all(),
            'pilihan' => $this->pilihan(),
        ]);
    }

    public function show(KontenPemasaran $konten): Response
    {
        $konten->load(['versiTerbit', 'versiDraf', 'kampanye:Id,Kode,Nama', 'tautanKeyword.keyword:Id,Keyword']);
        $disunting = $konten->versiDraf ?? $konten->versiTerbit;

        return Inertia::render('Pemasaran/KontenDetail', [
            'wajib' => ['konten' => AturanWajib::untuk(SimpanKontenPemasaranRequest::class), 'keyword' => AturanWajib::untuk(SimpanKeywordSeoRequest::class)],
            'konten' => [
                ...$this->ringkas($konten),
                ...$this->isiVersi($disunting),
            ],
            'versi' => $this->riwayat($konten),
            'keyword' => $this->daftarKeyword(),
            'pilihan' => $this->pilihan(),
        ]);
    }

    public function store(SimpanKontenPemasaranRequest $request, SimpanDrafKonten $aksi): RedirectResponse
    {
        $konten = $aksi->jalankan(null, $request->validated());

        $this->audit->catat('KontenPemasaran.Dibuat', 'KontenPemasaran', $konten->Id, dataSesudah: [
            'Slug' => $konten->Slug,
        ]);

        return redirect()
            ->route('pemasaran.konten.show', $konten->Id)
            ->with('sukses', 'Konten berhasil dibuat sebagai draf.');
    }

    public function update(
        SimpanKontenPemasaranRequest $request,
        KontenPemasaran $konten,
        SimpanDrafKonten $aksi,
    ): RedirectResponse {
        $aksi->jalankan($konten, $request->validated());

        return back()->with('sukses', 'Draf baru berhasil disimpan.');
    }

    public function terbitkan(KontenPemasaran $konten, TerbitkanKonten $aksi): RedirectResponse
    {
        $aksi->jalankan($konten);

        return back()->with('sukses', 'Konten berhasil diterbitkan.');
    }

    public function ubahStatus(
        Request $request,
        KontenPemasaran $konten,
        UbahStatusKonten $aksi,
    ): RedirectResponse {
        /** @var array{Status: string} $sah */
        $sah = $request->validate([
            'Status' => ['required', Rule::enum(StatusHalamanPemasaran::class)],
        ]);

        $aksi->jalankan($konten, StatusHalamanPemasaran::from($sah['Status']));

        return back()->with('sukses', "Konten berpindah ke {$sah['Status']}.");
    }

    public function simpanKeyword(SimpanKeywordSeoRequest $request): RedirectResponse
    {
        $keyword = KeywordSeo::create($request->validated());

        $this->audit->catat('KeywordSeo.Dibuat', 'KeywordSeo', $keyword->Id, dataSesudah: [
            'Keyword' => $keyword->Keyword,
        ]);

        return back()->with('sukses', 'Keyword ditambahkan.');
    }

    public function perbaruiKeyword(SimpanKeywordSeoRequest $request, KeywordSeo $keyword): RedirectResponse
    {
        $keyword->update($request->validated());

        return back()->with('sukses', 'Keyword diperbarui.');
    }

    public function simpanCluster(Request $request): RedirectResponse
    {
        /** @var array{Kode: string, Nama: string, Keterangan?: string|null} $sah */
        $sah = $request->validate([
            'Kode' => ['required', 'string', 'max:80', Rule::unique('ClusterSeo', 'Kode')],
            'Nama' => ['required', 'string', 'max:190'],
            'Keterangan' => ['nullable', 'string', 'max:500'],
        ]);

        ClusterSeo::create($sah);

        return back()->with('sukses', 'Cluster ditambahkan.');
    }

    /** Satu konten hanya punya satu keyword utama; menaikkan yang baru menurunkan yang lama. */
    public function tautkanKeyword(Request $request, KontenPemasaran $konten): RedirectResponse
    {
        /** @var array{KeywordSeoId: string, Utama?: bool} $sah */
        $sah = $request->validate([
            'KeywordSeoId' => ['required', 'string', 'exists:KeywordSeo,Id'],
            'Utama' => ['boolean'],
        ]);

        $utama = (bool) ($sah['Utama'] ?? false);

        if ($utama) {
            KontenKeywordSeo::query()
                ->where('KontenPemasaranId', $konten->Id)
                ->update(['Utama' => false]);
        }

        KontenKeywordSeo::query()->updateOrCreate(
            ['KontenPemasaranId' => $konten->Id, 'KeywordSeoId' => $sah['KeywordSeoId']],
            ['Utama' => $utama],
        );

        return back()->with('sukses', 'Keyword ditautkan ke konten.');
    }

    public function lepasKeyword(KontenPemasaran $konten, KeywordSeo $keyword): RedirectResponse
    {
        KontenKeywordSeo::query()
            ->where('KontenPemasaranId', $konten->Id)
            ->where('KeywordSeoId', $keyword->Id)
            ->delete();

        return back()->with('sukses', 'Keyword dilepas dari konten.');
    }

    /** Pratinjau memakai tautan bertanda tangan yang sama dengan halaman pemasaran. */
    public function pratinjau(KontenPemasaran $konten, VersiKontenPemasaran $versi): RedirectResponse
    {
        if ($versi->KontenPemasaranId !== $konten->Id) {
            throw new AturanBisnisDilanggar('Versi tersebut bukan milik konten ini.');
        }

        if (! $this->host->situsPublikAktif()) {
            throw new AturanBisnisDilanggar('Situs publik belum dikonfigurasi, pratinjau tidak tersedia.');
        }

        return redirect()->away(URL::temporarySignedRoute(
            'publik.konten.pratinjau',
            now()->addHour(),
            ['konten' => $konten->Id, 'versi' => $versi->Id],
        ));
    }

    /** @return array<string, mixed> */
    private function ringkas(KontenPemasaran $konten): array
    {
        return [
            'Id' => $konten->Id,
            'Slug' => $konten->Slug,
            'Ruas' => basename($konten->Slug),
            'Jenis' => $konten->Jenis->value,
            'Judul' => $konten->Judul,
            'Status' => $konten->Status->value,
            'PenulisNama' => $konten->PenulisNama,
            'KampanyeId' => $konten->KampanyeId,
            'Kampanye' => $konten->kampanye?->Nama,
            'NoIndex' => $konten->NoIndex,
            'DiSitemap' => $konten->bolehMasukSitemap(),
            'TerbitPada' => $konten->TerbitPada?->toIso8601String(),
            'VersiTerbitNomor' => $konten->versiTerbit?->Nomor,
            'VersiDrafNomor' => $konten->versiDraf?->Nomor,
            'VersiDrafId' => $konten->VersiDrafId,
            'UrlPublik' => $this->host->urlKanonik($konten->Slug),
            'Keyword' => $konten->tautanKeyword
                ->map(fn (KontenKeywordSeo $tautan): array => [
                    'Id' => $tautan->KeywordSeoId,
                    'Keyword' => $tautan->keyword->Keyword,
                    'Utama' => $tautan->Utama,
                ])
                ->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function isiVersi(?VersiKontenPemasaran $versi): array
    {
        if ($versi === null) {
            return ['IsiMarkdown' => ''];
        }

        return [
            'Ringkasan' => $versi->Ringkasan,
            'IsiMarkdown' => $versi->IsiMarkdown,
            'MetaJudul' => $versi->MetaJudul,
            'MetaDeskripsi' => $versi->MetaDeskripsi,
            'Kanonik' => $versi->Kanonik,
            'OgJudul' => $versi->OgJudul,
            'OgDeskripsi' => $versi->OgDeskripsi,
            'OgGambar' => $versi->OgGambar,
            'SkemaTipe' => $versi->SkemaTipe,
        ];
    }

    /** @return list<array<string, mixed>> */
    private function riwayat(KontenPemasaran $konten): array
    {
        return array_values(VersiKontenPemasaran::query()
            ->where('KontenPemasaranId', $konten->Id)
            ->orderByDesc('Nomor')
            ->limit(50)
            ->get()
            ->map(fn (VersiKontenPemasaran $versi): array => [
                'Id' => $versi->Id,
                'Nomor' => $versi->Nomor,
                'Judul' => $versi->Judul,
                'Catatan' => $versi->Catatan,
                'DibuatPada' => $versi->DibuatPada->toIso8601String(),
                'Terbit' => $versi->Id === $konten->VersiTerbitId,
                'Draf' => $versi->Id === $konten->VersiDrafId,
            ])
            ->all());
    }

    /** @return list<array<string, mixed>> */
    private function daftarKeyword(): array
    {
        return array_values(KeywordSeo::query()
            ->with('cluster:Id,Nama')
            ->orderBy('Keyword')
            ->get()
            ->map(fn (KeywordSeo $satu): array => [
                'Id' => $satu->Id,
                'Keyword' => $satu->Keyword,
                'ClusterSeoId' => $satu->ClusterSeoId,
                'Cluster' => $satu->cluster?->Nama,
                'Intent' => $satu->Intent->value,
                'IntentLabel' => $satu->Intent->label(),
                'TargetUrl' => $satu->TargetUrl,
                'Prioritas' => $satu->Prioritas->value,
                'Urutan' => $satu->Prioritas->urutan(),
                'Status' => $satu->Status->value,
                'Catatan' => $satu->Catatan,
            ])
            ->all());
    }

    /** @return array<string, mixed> */
    private function pilihan(): array
    {
        return [
            'Jenis' => array_column(JenisKontenPemasaran::cases(), 'value'),
            'AwalanJalur' => $this->awalanJalur(),
            'Status' => array_column(StatusHalamanPemasaran::cases(), 'value'),
            'Intent' => $this->intent(),
            'Prioritas' => array_column(PrioritasKeyword::cases(), 'value'),
            'StatusKeyword' => array_column(StatusKeywordSeo::cases(), 'value'),
            'Kampanye' => Kampanye::query()->orderBy('Kode')->pluck('Kode', 'Id')->all(),
        ];
    }

    /** @return array<string, string> */
    private function awalanJalur(): array
    {
        $peta = [];

        foreach (JenisKontenPemasaran::cases() as $satu) {
            $peta[$satu->value] = $satu->awalanJalur();
        }

        return $peta;
    }

    /** @return array<string, string> */
    private function intent(): array
    {
        $peta = [];

        foreach (IntentKeyword::cases() as $satu) {
            $peta[$satu->value] = $satu->label();
        }

        return $peta;
    }
}
