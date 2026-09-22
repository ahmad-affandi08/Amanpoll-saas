<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Http\Controllers;

use App\Domain\Langganan\Application\Actions\KelolaLangganan;
use App\Domain\Langganan\Application\Actions\TerbitkanTagihanLangganan;
use App\Domain\Langganan\Application\Services\LayananLangganan;
use App\Domain\Langganan\Domain\Enums\SiklusLangganan;
use App\Domain\Langganan\Infrastructure\Persistence\Models\Langganan;
use App\Domain\Langganan\Infrastructure\Persistence\Models\PaketLangganan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Pengelolaan langganan seluruh tenant oleh admin platform (22.04). */
final class LanggananPlatformController extends Controller
{
    public function __construct(private readonly LayananLangganan $layananLangganan) {}

    public function index(): Response
    {
        $langganan = Langganan::query()
            ->withoutGlobalScopes()
            ->with(['paketLangganan', 'organisasi'])
            ->orderByDesc('MulaiPada')
            ->get();

        return Inertia::render('Platform/Langganan/Index', [
            'langganan' => $langganan
                ->map(fn (Langganan $satu): array => [
                    'Id' => $satu->Id,
                    'OrganisasiId' => $satu->OrganisasiId,
                    'NamaOrganisasi' => $satu->organisasi?->Nama,
                    'KodeOrganisasi' => $satu->organisasi?->Kode,
                    'NamaPaket' => $satu->paketLangganan?->Nama,
                    'Siklus' => $satu->Siklus,
                    'MulaiPada' => $satu->MulaiPada->toDateString(),
                    'BerakhirPada' => $satu->BerakhirPada?->toDateString(),
                    'UjiCobaSampai' => $satu->UjiCobaSampai?->toDateString(),
                    'StatusTersimpan' => $satu->Status,
                    'StatusEfektif' => $this->layananLangganan->statusEfektif($satu)->value,
                    'LabelStatusEfektif' => $this->layananLangganan->statusEfektif($satu)->label(),
                    'BatalPada' => $satu->BatalPada?->toIso8601String(),
                ])
                ->all(),
            'organisasi' => Organisasi::query()
                ->withoutGlobalScopes()
                ->orderBy('Nama')
                ->get(['Id', 'Nama', 'Kode'])
                ->all(),
            'paket' => PaketLangganan::query()
                ->where('Aktif', true)
                ->orderBy('Nama')
                ->get(['Id', 'Nama', 'HargaBulanan', 'HargaTahunan'])
                ->all(),
            'siklus' => array_map(
                fn (SiklusLangganan $satu): array => ['Nilai' => $satu->value, 'Label' => $satu->label()],
                SiklusLangganan::cases(),
            ),
        ]);
    }

    public function store(Request $request, KelolaLangganan $aksi): RedirectResponse
    {
        $data = $request->validate([
            'OrganisasiId' => ['required', 'string', 'max:26', Rule::exists('Organisasi', 'Id')],
            'PaketLanggananId' => ['required', 'string', 'max:26', Rule::exists('PaketLangganan', 'Id')],
            'Siklus' => ['required', Rule::enum(SiklusLangganan::class)],
            'MulaiPada' => ['nullable', 'date'],
            'DenganUjiCoba' => ['nullable', 'boolean'],
            'UjiCobaSampai' => ['nullable', 'date'],
        ]);

        $aksi->mulai((string) $data['OrganisasiId'], $data);

        return back()->with('sukses', 'Langganan organisasi diperbarui.');
    }

    public function perpanjang(Langganan $langgananPlatform, KelolaLangganan $aksi): RedirectResponse
    {
        $aksi->perpanjang($langgananPlatform);

        return back()->with('sukses', 'Langganan diperpanjang satu periode.');
    }

    public function batalkan(Request $request, Langganan $langgananPlatform, KelolaLangganan $aksi): RedirectResponse
    {
        $aksi->batalkan($langgananPlatform, $request->boolean('Segera'));

        return back()->with('sukses', 'Langganan dibatalkan.');
    }

    public function terbitkanTagihan(Langganan $langgananPlatform, TerbitkanTagihanLangganan $aksi): RedirectResponse
    {
        $tagihan = $aksi->jalankan($langgananPlatform);

        return back()->with('sukses', "Tagihan {$tagihan->Nomor} diterbitkan.");
    }
}
