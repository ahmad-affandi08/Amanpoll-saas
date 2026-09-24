<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Http\Controllers;

use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerjaAset;
use App\Domain\Persediaan\Domain\Enums\StatusSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\PelaksanaanDaftarPeriksa;
use App\Domain\Sinkronisasi\Application\Services\PenyusunLayarTeknisi;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Menyiapkan Mode Lapangan (DESIGN §36.6 layar 02).
 *
 * Unduhannya sendiri dikerjakan klien: paket offline FASE 20 (`offline.paket`)
 * disimpan ke IndexedDB, lalu layar tiket, checklist, aset, dan suku cadang
 * dibuka sekali supaya service worker menyimpannya untuk dipakai tanpa sinyal.
 * Controller ini hanya memberi angka yang ditampilkan di tiap baris unduhan.
 */
final class LapanganTeknisiSiapkanController extends Controller
{
    public function __invoke(Request $request, PenyusunLayarTeknisi $penyusun): Response
    {
        $pengguna = $request->user('web');
        $tiketId = $penyusun->kueriDitugaskan($pengguna)
            ->whereIn('Status', array_map(fn ($status): string => $status->value, PenyusunLayarTeknisi::STATUS_AKTIF))
            ->limit(PenyusunLayarTeknisi::BATAS_TIKET)
            ->pluck('Id');

        $asetId = PerintahKerjaAset::query()->whereIn('PerintahKerjaId', $tiketId)->distinct()->pluck('AsetId');

        return Inertia::render('Lapangan/Teknisi/Siapkan', [
            'tiketId' => $tiketId->values()->all(),
            'asetId' => $asetId->values()->all(),
            'jumlah' => [
                'Tiket' => $tiketId->count(),
                'Aset' => $asetId->count(),
                'Lokasi' => Aset::query()->whereIn('Id', $asetId)->whereNotNull('LokasiId')->distinct()->count('LokasiId'),
                'Templat' => PelaksanaanDaftarPeriksa::query()->whereIn('PerintahKerjaId', $tiketId)->distinct()->count('TemplatDaftarPeriksaId'),
                'SukuCadang' => SukuCadang::query()->where('Status', StatusSukuCadang::Aktif->value)->count(),
            ],
        ]);
    }
}
