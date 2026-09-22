<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Controllers;

use App\Core\Audit\LayananAudit;
use App\Domain\Pemasaran\Application\Services\LayananTemplateWhatsApp;
use App\Domain\Pemasaran\Application\Services\PenjawabWhatsAppMasuk;
use App\Domain\Pemasaran\Application\Services\PerenderTemplateWhatsApp;
use App\Domain\Pemasaran\Domain\Enums\StatusPengirimanWhatsApp;
use App\Domain\Pemasaran\Domain\Enums\StatusPersetujuanTemplateWa;
use App\Domain\Pemasaran\Http\Requests\SimpanMenuWhatsAppRequest;
use App\Domain\Pemasaran\Http\Requests\SimpanTemplateWhatsAppRequest;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\MenuWhatsAppPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\TemplateWhatsAppPemasaran;
use App\Http\Controllers\Controller;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Konsol template, menu, dan kiriman WhatsApp (MARKETING.md 16). */
final class WhatsAppPemasaranController extends Controller
{
    public function __construct(
        private readonly LayananTemplateWhatsApp $layananTemplate,
        private readonly PenjawabWhatsAppMasuk $penjawab,
        private readonly PerenderTemplateWhatsApp $perender,
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananAudit $audit,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Pemasaran/WhatsApp', [
            'template' => TemplateWhatsAppPemasaran::query()
                ->orderBy('Kode')
                ->get()
                ->map(fn (TemplateWhatsAppPemasaran $satu): array => [
                    'Id' => $satu->Id,
                    'Kode' => $satu->Kode,
                    'Nama' => $satu->Nama,
                    'Bahasa' => $satu->Bahasa,
                    'Kategori' => $satu->Kategori,
                    'IsiTeks' => $satu->IsiTeks,
                    'StatusPersetujuan' => $satu->StatusPersetujuan->value,
                    'AlasanPenolakan' => $satu->AlasanPenolakan,
                    'IdTemplatePenyedia' => $satu->IdTemplatePenyedia,
                    'DiperiksaPada' => $satu->DiperiksaPada?->toIso8601String(),
                    'Aktif' => $satu->Aktif,
                    'SiapKirim' => $satu->siapKirim(),
                    'TujuanStatus' => array_map(
                        fn (StatusPersetujuanTemplateWa $tujuan): string => $tujuan->value,
                        $satu->StatusPersetujuan->tujuanSah(),
                    ),
                ])->all(),
            'menu' => MenuWhatsAppPemasaran::query()
                ->orderBy('Urutan')
                ->get()
                ->map(fn (MenuWhatsAppPemasaran $satu): array => [
                    'Id' => $satu->Id,
                    'Kunci' => $satu->Kunci,
                    'Urutan' => $satu->Urutan,
                    'Label' => $satu->Label,
                    'Balasan' => $satu->Balasan,
                    'Aktif' => $satu->Aktif,
                ])->all(),
            'pratinjauMenu' => $this->penjawab->menu(),
            'kataBerhenti' => $this->penjawab->kataBerhenti(),
            'ringkasanKiriman' => $this->ringkasanKiriman(),
            'variabel' => $this->perender->variabelDikenal(),
            'pilihan' => [
                'Status' => array_column(StatusPersetujuanTemplateWa::cases(), 'value'),
            ],
        ]);
    }

    public function simpanTemplate(SimpanTemplateWhatsAppRequest $request): RedirectResponse
    {
        $template = TemplateWhatsAppPemasaran::create($request->validated());

        $this->audit->catat('TemplateWhatsApp.Dibuat', 'TemplateWhatsAppPemasaran', $template->Id, dataSesudah: [
            'Kode' => $template->Kode,
        ]);

        return back()->with('sukses', 'Template WhatsApp dibuat sebagai draf.');
    }

    /** Yang disetujui penyedia adalah naskah lamanya, jadi mengubah naskah membatalkan persetujuannya. */
    public function perbaruiTemplate(
        SimpanTemplateWhatsAppRequest $request,
        TemplateWhatsAppPemasaran $template,
    ): RedirectResponse {
        $data = $request->validated();
        $naskahBerubah = $template->IsiTeks !== $data['IsiTeks'];

        $template->update($data);

        if ($naskahBerubah && $template->StatusPersetujuan !== StatusPersetujuanTemplateWa::Draf) {
            $template->StatusPersetujuan = StatusPersetujuanTemplateWa::Draf;
            $template->IdTemplatePenyedia = null;
            $template->AlasanPenolakan = 'Naskah diubah setelah diajukan; persetujuan lama tidak berlaku.';
            $template->save();
        }

        $this->audit->catat('TemplateWhatsApp.Diubah', 'TemplateWhatsAppPemasaran', $template->Id, dataSesudah: [
            'Kode' => $template->Kode,
            'StatusPersetujuan' => $template->StatusPersetujuan->value,
        ]);

        return back()->with('sukses', 'Template WhatsApp diperbarui.');
    }

    public function ajukanTemplate(TemplateWhatsAppPemasaran $template): RedirectResponse
    {
        $status = $this->layananTemplate->ajukan($template);

        return back()->with('sukses', "Template diajukan; statusnya kini {$status->value}.");
    }

    public function periksaTemplate(TemplateWhatsAppPemasaran $template): RedirectResponse
    {
        $status = $this->layananTemplate->periksa($template);

        return back()->with('sukses', "Status template menurut penyedia: {$status->value}.");
    }

    public function catatKeputusan(Request $request, TemplateWhatsAppPemasaran $template): RedirectResponse
    {
        /** @var array{Status: string, IdTemplatePenyedia?: string|null, Alasan?: string|null} $sah */
        $sah = $request->validate([
            'Status' => ['required', Rule::enum(StatusPersetujuanTemplateWa::class)],
            'IdTemplatePenyedia' => ['nullable', 'string', 'max:190'],
            'Alasan' => ['nullable', 'string', 'max:500'],
        ]);

        $status = $this->layananTemplate->catatKeputusanManual(
            $template,
            StatusPersetujuanTemplateWa::from($sah['Status']),
            $sah['IdTemplatePenyedia'] ?? null,
            $sah['Alasan'] ?? null,
        );

        return back()->with('sukses', "Keputusan penyedia dicatat: {$status->value}.");
    }

    /** Menu dikirim utuh: butir yang hilang dari kiriman berarti dicabut. */
    public function simpanMenu(SimpanMenuWhatsAppRequest $request): RedirectResponse
    {
        /** @var list<array{Kunci: string, Label: string, Balasan: string, Aktif: bool}> $menu */
        $menu = $request->validated()['Menu'];

        $this->transaksi->jalankan(function () use ($menu): void {
            $kunci = array_map(fn (array $satu): string => $satu['Kunci'], $menu);

            MenuWhatsAppPemasaran::query()->whereNotIn('Kunci', $kunci)->delete();

            foreach ($menu as $urutan => $satu) {
                MenuWhatsAppPemasaran::query()->updateOrCreate(
                    ['Kunci' => $satu['Kunci']],
                    [
                        'Urutan' => $urutan,
                        'Label' => $satu['Label'],
                        'Balasan' => $satu['Balasan'],
                        'Aktif' => $satu['Aktif'],
                    ],
                );
            }
        });

        $this->audit->catat('MenuWhatsApp.Disimpan', 'MenuWhatsAppPemasaran', null, dataSesudah: [
            'Jumlah' => count($menu),
        ]);

        return back()->with('sukses', 'Menu WhatsApp tersimpan.');
    }

    /** @return array<string, int> */
    private function ringkasanKiriman(): array
    {
        $jumlah = DB::table('PengirimanWhatsAppPemasaran')
            ->selectRaw('Status, COUNT(*) as Jumlah')
            ->groupBy('Status')
            ->pluck('Jumlah', 'Status');

        $hasil = [];

        foreach (StatusPengirimanWhatsApp::cases() as $satu) {
            $hasil[$satu->value] = (int) ($jumlah[$satu->value] ?? 0);
        }

        return $hasil;
    }
}
