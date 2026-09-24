<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Http\Controllers;

use App\Domain\Aset\Application\Services\PencariAsetLewatKode;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Sinkronisasi\Application\Services\PenyusunLayarTeknisi;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Kamera pindai dan lembar "Aset ditemukan" (DESIGN §36.6 layar 13–14, PRD 10).
 *
 * Kode dari kamera atau isian ketik diselesaikan dengan resolver yang sama
 * dengan label QR (`PencariAsetLewatKode`), lalu diperiksa `AsetPolicy::view`.
 * `?aset=` dipakai pengalihan `aset.pindai` untuk teknisi. Aset milik
 * organisasi lain atau di luar lingkup tidak pernah termuat, jadi hasilnya
 * sama dengan kode yang tidak dikenal.
 */
final class LapanganTeknisiPindaiController extends Controller
{
    public function __invoke(Request $request, PencariAsetLewatKode $pencari, PenyusunLayarTeknisi $penyusun): Response
    {
        $pengguna = $request->user('web');
        $kode = trim((string) $request->query('kode', ''));
        $asetId = trim((string) $request->query('aset', ''));
        $galat = null;
        $aset = null;

        if ($kode !== '') {
            $aset = $pencari->cari($kode);
            $galat = $aset === null ? "Kode {$kode} tidak cocok dengan aset mana pun." : null;
        } elseif ($asetId !== '') {
            $aset = Aset::query()->find($asetId);
            $galat = $aset === null ? 'Aset itu tidak ditemukan.' : null;
        }

        $tanpaIzin = $aset !== null && Gate::forUser($pengguna)->denies('view', $aset);

        return Inertia::render('Lapangan/Teknisi/Pindai', [
            'asetDitemukan' => $aset === null || $tanpaIzin ? null : $penyusun->detailAset($aset, $pengguna),
            'galatPindai' => $tanpaIzin ? 'Kamu tidak punya izin melihat aset ini.' : $galat,
            'tanpaIzin' => $tanpaIzin,
        ]);
    }
}
