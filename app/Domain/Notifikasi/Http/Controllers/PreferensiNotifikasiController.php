<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Http\Controllers;

use App\Domain\Notifikasi\Application\Actions\SimpanPreferensiNotifikasi;
use App\Domain\Notifikasi\Domain\KatalogPeristiwaNotifikasi;
use App\Domain\Notifikasi\Http\Requests\SimpanPreferensiNotifikasiRequest;
use App\Domain\Notifikasi\Infrastructure\Persistence\Models\PreferensiNotifikasi;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PreferensiNotifikasiController extends Controller
{
    public function halaman(): Response
    {
        return Inertia::render('PreferensiNotifikasi/Index');
    }

    public function index(Request $request): JsonResponse
    {
        $preferensiTersimpan = PreferensiNotifikasi::query()
            ->where('PenggunaId', $request->user('web')->Id)
            ->get()
            ->keyBy(fn (PreferensiNotifikasi $p) => "{$p->JenisPeristiwa}#{$p->Kanal}");

        $hasil = [];
        foreach (KatalogPeristiwaNotifikasi::daftar() as $kode => $label) {
            foreach (['InApp', 'Email'] as $kanal) {
                $preferensi = $preferensiTersimpan->get("{$kode}#{$kanal}");
                $hasil[] = [
                    'JenisPeristiwa' => $kode,
                    'Label' => $label,
                    'Kanal' => $kanal,
                    'Aktif' => $preferensi === null || $preferensi->Aktif,
                ];
            }
        }

        return response()->json(['data' => $hasil]);
    }

    public function store(SimpanPreferensiNotifikasiRequest $request, SimpanPreferensiNotifikasi $aksi): RedirectResponse
    {
        $data = $request->validated();

        $aksi->jalankan($request->user('web')->Id, $data['JenisPeristiwa'], $data['Kanal'], $data['Aktif']);

        return back()->with('sukses', 'Preferensi notifikasi berhasil disimpan.');
    }
}
