<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Controllers;

use App\Domain\Pemasaran\Application\Services\LayananTemplateEmail;
use App\Domain\Pemasaran\Application\Services\PerenderTemplateEmail;
use App\Domain\Pemasaran\Domain\Enums\JenisTemplateEmail;
use App\Domain\Pemasaran\Http\Requests\SimpanTemplateEmailRequest;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\TemplateEmailPemasaran;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/** Template email pemasaran di konsol platform (MARKETING.md 15). */
final class TemplateEmailController extends Controller
{
    public function __construct(
        private readonly LayananTemplateEmail $layanan,
        private readonly PerenderTemplateEmail $perender,
    ) {}

    public function index(): Response
    {
        $template = TemplateEmailPemasaran::query()->orderBy('Kode')->get();

        return Inertia::render('Pemasaran/Email/Template', [
            'template' => $template->map(fn (TemplateEmailPemasaran $satu): array => [
                'Id' => $satu->Id,
                'Kode' => $satu->Kode,
                'Nama' => $satu->Nama,
                'Jenis' => $satu->Jenis->value,
                'Subjek' => $satu->Subjek,
                'IsiHtml' => $satu->IsiHtml,
                'IsiTeks' => $satu->IsiTeks,
                'Aktif' => $satu->Aktif,
            ])->all(),
            'pilihan' => [
                'Jenis' => array_column(JenisTemplateEmail::cases(), 'value'),
                'Variabel' => $this->perender->variabelDikenal(),
            ],
        ]);
    }

    public function store(SimpanTemplateEmailRequest $request): RedirectResponse
    {
        $this->layanan->simpan(null, $this->data($request));

        return back()->with('sukses', 'Template email berhasil dibuat.');
    }

    public function update(
        SimpanTemplateEmailRequest $request,
        TemplateEmailPemasaran $template,
    ): RedirectResponse {
        $this->layanan->simpan($template, $this->data($request));

        return back()->with('sukses', 'Template email berhasil diperbarui.');
    }

    public function destroy(TemplateEmailPemasaran $template): RedirectResponse
    {
        $this->layanan->hapus($template);

        return back()->with('sukses', 'Template email berhasil dihapus.');
    }

    /** @return array{Kode: string, Nama: string, Jenis: string, Subjek: string, IsiHtml: string, IsiTeks: string|null, Aktif: bool} */
    private function data(SimpanTemplateEmailRequest $request): array
    {
        /** @var array{Kode: string, Nama: string, Jenis: string, Subjek: string, IsiHtml: string, IsiTeks?: string|null, Aktif?: bool} $sah */
        $sah = $request->validated();

        return [
            'Kode' => $sah['Kode'],
            'Nama' => $sah['Nama'],
            'Jenis' => $sah['Jenis'],
            'Subjek' => $sah['Subjek'],
            'IsiHtml' => $sah['IsiHtml'],
            'IsiTeks' => $sah['IsiTeks'] ?? null,
            'Aktif' => $sah['Aktif'] ?? false,
        ];
    }
}
