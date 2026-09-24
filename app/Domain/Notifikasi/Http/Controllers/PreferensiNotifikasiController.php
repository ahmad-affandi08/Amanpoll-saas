<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Http\Controllers;

use App\Domain\Notifikasi\Application\Actions\SimpanPreferensiNotifikasi;
use App\Domain\Notifikasi\Application\Services\TujuanWhatsAppNotifikasi;
use App\Domain\Notifikasi\Domain\Enums\KanalNotifikasi;
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

    public function index(Request $request, TujuanWhatsAppNotifikasi $tujuanWhatsApp): JsonResponse
    {
        $penggunaId = $request->user('web')->Id;
        $preferensiTersimpan = PreferensiNotifikasi::query()
            ->where('PenggunaId', $penggunaId)
            ->get()
            ->keyBy(fn (PreferensiNotifikasi $p) => "{$p->JenisPeristiwa}#{$p->Kanal}");

        $hasil = [];
        foreach (KatalogPeristiwaNotifikasi::daftar() as $kode => $label) {
            foreach (KanalNotifikasi::cases() as $kanal) {
                $preferensi = $preferensiTersimpan->get("{$kode}#{$kanal->value}");
                $hasil[] = [
                    'JenisPeristiwa' => $kode,
                    'Label' => $label,
                    'Kanal' => $kanal->value,
                    'Aktif' => $preferensi->Aktif ?? KatalogPeristiwaNotifikasi::aktifBawaan($kode, $kanal),
                ];
            }
        }

        // Preferensi WhatsApp boleh diatur lebih dulu; halaman menjelaskan mengapa pesannya belum akan datang.
        return response()->json([
            'data' => $hasil,
            'whatsapp' => [
                'PenyediaAktif' => $tujuanWhatsApp->penyediaAktif(),
                'NomorValid' => $tujuanWhatsApp->nomorUntuk($penggunaId) !== null,
            ],
        ]);
    }

    public function store(SimpanPreferensiNotifikasiRequest $request, SimpanPreferensiNotifikasi $aksi): RedirectResponse
    {
        $data = $request->validated();

        $aksi->jalankan($request->user('web')->Id, $data['JenisPeristiwa'], $data['Kanal'], $data['Aktif']);

        return back()->with('sukses', 'Preferensi notifikasi berhasil disimpan.');
    }
}
