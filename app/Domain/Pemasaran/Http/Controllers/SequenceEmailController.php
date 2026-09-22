<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Controllers;

use App\Domain\Pemasaran\Application\Services\LayananSequenceEmail;
use App\Domain\Pemasaran\Domain\Enums\StatusPendaftaranSequence;
use App\Domain\Pemasaran\Domain\KatalogKonfigurasiPemasaran;
use App\Domain\Pemasaran\Http\Requests\SimpanLangkahSequenceRequest;
use App\Domain\Pemasaran\Http\Requests\SimpanSequenceEmailRequest;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\LangkahSequenceEmail;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\SequenceEmailPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\TemplateEmailPemasaran;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Validasi\AturanWajib;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/** Sequence email pemasaran di konsol platform (MARKETING.md 15). */
final class SequenceEmailController extends Controller
{
    public function __construct(private readonly LayananSequenceEmail $layanan) {}

    public function index(): Response
    {
        $sequence = SequenceEmailPemasaran::query()
            ->with(['langkah.template'])
            ->withCount([
                'pendaftaran as jumlah_berjalan' => fn ($kueri) => $kueri
                    ->where('Status', StatusPendaftaranSequence::Berjalan->value),
            ])
            ->orderBy('Kode')
            ->get();

        return Inertia::render('Pemasaran/Email/Sequence', [
            'wajib' => ['sequence' => AturanWajib::untuk(SimpanSequenceEmailRequest::class), 'langkah' => AturanWajib::untuk(SimpanLangkahSequenceRequest::class)],
            'sequence' => $sequence->map(fn (SequenceEmailPemasaran $satu): array => [
                'Id' => $satu->Id,
                'Kode' => $satu->Kode,
                'Nama' => $satu->Nama,
                'Keterangan' => $satu->Keterangan,
                'Aktif' => $satu->Aktif,
                'JumlahBerjalan' => (int) ($satu->jumlah_berjalan ?? 0),
                'Langkah' => array_values($satu->langkah
                    ->map(fn (LangkahSequenceEmail $langkah): array => [
                        'Id' => $langkah->Id,
                        'TemplateEmailPemasaranId' => $langkah->TemplateEmailPemasaranId,
                        'TemplateNama' => (string) ($langkah->template->Nama ?? 'Template terhapus'),
                        'Urutan' => $langkah->Urutan,
                        'HariKe' => $langkah->HariKe,
                        'Aktif' => $langkah->Aktif,
                    ])->all()),
            ])->all(),
            'template' => TemplateEmailPemasaran::query()
                ->where('Aktif', true)
                ->orderBy('Nama')
                ->get()
                ->map(fn (TemplateEmailPemasaran $satu): array => [
                    'Id' => $satu->Id,
                    'Nama' => $satu->Nama,
                    'Kode' => $satu->Kode,
                ])->all(),
            'kodeSequenceTrial' => KatalogKonfigurasiPemasaran::EMAIL_SEQUENCE_TRIAL,
        ]);
    }

    public function store(SimpanSequenceEmailRequest $request): RedirectResponse
    {
        $this->layanan->simpan(null, $this->data($request));

        return back()->with('sukses', 'Sequence berhasil dibuat.');
    }

    public function update(
        SimpanSequenceEmailRequest $request,
        SequenceEmailPemasaran $sequence,
    ): RedirectResponse {
        $this->layanan->simpan($sequence, $this->data($request));

        return back()->with('sukses', 'Sequence berhasil diperbarui.');
    }

    public function simpanLangkah(
        SimpanLangkahSequenceRequest $request,
        SequenceEmailPemasaran $sequence,
        ?LangkahSequenceEmail $langkah = null,
    ): RedirectResponse {
        /** @var array{TemplateEmailPemasaranId: string, Urutan: int, HariKe: int, Aktif?: bool} $sah */
        $sah = $request->validated();

        $this->layanan->simpanLangkah($sequence, $langkah, [
            'TemplateEmailPemasaranId' => $sah['TemplateEmailPemasaranId'],
            'Urutan' => $sah['Urutan'],
            'HariKe' => $sah['HariKe'],
            'Aktif' => $sah['Aktif'] ?? false,
        ]);

        return back()->with('sukses', 'Langkah sequence berhasil disimpan.');
    }

    public function hapusLangkah(
        SequenceEmailPemasaran $sequence,
        LangkahSequenceEmail $langkah,
    ): RedirectResponse {
        $this->layanan->hapusLangkah($sequence, $langkah);

        return back()->with('sukses', 'Langkah sequence berhasil dihapus.');
    }

    /** @return array{Kode: string, Nama: string, Keterangan: string|null, Aktif: bool} */
    private function data(SimpanSequenceEmailRequest $request): array
    {
        /** @var array{Kode: string, Nama: string, Keterangan?: string|null, Aktif?: bool} $sah */
        $sah = $request->validated();

        return [
            'Kode' => $sah['Kode'],
            'Nama' => $sah['Nama'],
            'Keterangan' => $sah['Keterangan'] ?? null,
            'Aktif' => $sah['Aktif'] ?? false,
        ];
    }
}
