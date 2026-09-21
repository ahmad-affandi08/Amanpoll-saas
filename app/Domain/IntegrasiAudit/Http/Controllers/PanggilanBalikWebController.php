<?php

declare(strict_types=1);

namespace App\Domain\IntegrasiAudit\Http\Controllers;

use App\Core\Audit\LayananAudit;
use App\Domain\IntegrasiAudit\Http\Requests\SimpanPanggilanBalikWebRequest;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\PanggilanBalikWeb;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\PengirimanPanggilanBalikWeb;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class PanggilanBalikWebController extends Controller
{
    public function __construct(private readonly LayananAudit $audit) {}

    public function store(SimpanPanggilanBalikWebRequest $request): RedirectResponse
    {
        $this->authorize('create', PanggilanBalikWeb::class);
        $data = $request->validated();

        $webhook = PanggilanBalikWeb::create([
            'Nama' => $data['Nama'],
            'Url' => $data['Url'],
            'Rahasia' => $data['Rahasia'],
            'Peristiwa' => $data['Peristiwa'],
            'Aktif' => $data['Aktif'] ?? true,
        ]);

        // Rahasia tidak ikut dicatat pada audit.
        $this->audit->catat('PanggilanBalikWeb.Dibuat', 'PanggilanBalikWeb', $webhook->Id, dataSesudah: [
            'Nama' => $webhook->Nama,
            'Url' => $webhook->Url,
            'Peristiwa' => $webhook->Peristiwa,
        ]);

        return back()->with('sukses', 'Panggilan balik web dibuat.');
    }

    public function update(SimpanPanggilanBalikWebRequest $request, PanggilanBalikWeb $panggilanBalikWeb): RedirectResponse
    {
        $this->authorize('update', $panggilanBalikWeb);
        $data = $request->validated();

        $panggilanBalikWeb->fill([
            'Nama' => $data['Nama'],
            'Url' => $data['Url'],
            'Peristiwa' => $data['Peristiwa'],
            'Aktif' => $data['Aktif'] ?? true,
        ]);

        // Rahasia yang dikosongkan berarti tetap memakai yang tersimpan.
        if (! empty($data['Rahasia'])) {
            $panggilanBalikWeb->Rahasia = $data['Rahasia'];
        }
        $panggilanBalikWeb->save();

        $this->audit->catat('PanggilanBalikWeb.Diubah', 'PanggilanBalikWeb', $panggilanBalikWeb->Id, dataSesudah: [
            'Nama' => $panggilanBalikWeb->Nama,
            'Aktif' => $panggilanBalikWeb->Aktif,
        ]);

        return back()->with('sukses', 'Panggilan balik web diperbarui.');
    }

    public function destroy(PanggilanBalikWeb $panggilanBalikWeb): RedirectResponse
    {
        $this->authorize('delete', $panggilanBalikWeb);
        $id = $panggilanBalikWeb->Id;
        $panggilanBalikWeb->delete();
        $this->audit->catat('PanggilanBalikWeb.Dihapus', 'PanggilanBalikWeb', $id);

        return back()->with('sukses', 'Panggilan balik web dihapus.');
    }

    public function riwayat(PanggilanBalikWeb $panggilanBalikWeb): Response
    {
        $this->authorize('view', $panggilanBalikWeb);

        $pengiriman = $panggilanBalikWeb->pengiriman()
            ->orderByDesc('DibuatPada')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (PengirimanPanggilanBalikWeb $item): array => [
                'Id' => $item->Id,
                'Peristiwa' => $item->Peristiwa,
                'Status' => $item->Status,
                'StatusHttp' => $item->StatusHttp,
                'Percobaan' => $item->Percobaan,
                'JadwalCobaLagiPada' => $item->JadwalCobaLagiPada?->toIso8601String(),
                'DikirimPada' => $item->DikirimPada?->toIso8601String(),
                'DibuatPada' => $item->DibuatPada->toIso8601String(),
                'Respons' => $item->Respons,
            ]);

        return Inertia::render('Integrasi/Pengiriman', [
            'webhook' => [
                'Id' => $panggilanBalikWeb->Id,
                'Nama' => $panggilanBalikWeb->Nama,
                'Url' => $panggilanBalikWeb->Url,
                'Aktif' => $panggilanBalikWeb->Aktif,
                'Peristiwa' => $panggilanBalikWeb->Peristiwa,
            ],
            'pengiriman' => $pengiriman,
        ]);
    }
}
