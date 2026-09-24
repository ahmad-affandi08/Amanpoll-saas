<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Http\Controllers;

use App\Core\Organisasi\KalenderOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\Inspeksi;
use App\Domain\Sinkronisasi\Application\Services\PenyusunLayarTeknisi;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Beranda Mode Lapangan untuk teknisi (DESIGN §36.6 layar 03 dan 16).
 *
 * Jadwal hari ini, tiket "Kerjakan sekarang", dan angka menu disusun klien
 * dari daftar tiket di sini, karena "hari ini" mengikuti jam perangkat dan
 * status tiket yang diubah saat offline hanya diketahui perangkat itu.
 */
final class LapanganTeknisiBerandaController extends Controller
{
    /** Banner agenda di bawah tiket; lebih dari ini tidak muat di karosel. */
    private const BATAS_AGENDA = 3;

    public function __invoke(Request $request, PenyusunLayarTeknisi $penyusun, KalenderOrganisasi $kalender): Response
    {
        $pengguna = $request->user('web');

        return Inertia::render('Lapangan/Teknisi/Beranda', [
            'tiket' => $penyusun->tiketAktif($pengguna),
            'selesai' => $penyusun->tiketSelesai($pengguna),
            'inspeksi' => $this->inspeksiMendatang($pengguna, $kalender),
            'lokasiSaya' => $this->lokasiSaya($pengguna),
        ]);
    }

    /**
     * Inspeksi terjadwal yang akan dilaksanakan pengguna ini, untuk banner agenda.
     *
     * @return list<array<string, mixed>>
     */
    private function inspeksiMendatang(Pengguna $pengguna, KalenderOrganisasi $kalender): array
    {
        return array_values(Inspeksi::query()
            ->with('aset:Id,Nama,LokasiId', 'aset.lokasi:Id,Nama')
            ->where('DilaksanakanOleh', $pengguna->Id)
            ->where('Status', '!=', 'Selesai')
            ->where('DijadwalkanPada', '>=', $kalender->awalHari($kalender->hariIni()))
            ->orderBy('DijadwalkanPada')
            ->limit(self::BATAS_AGENDA)
            ->get()
            ->map(fn (Inspeksi $inspeksi): array => [
                'Id' => $inspeksi->Id,
                'Nomor' => $inspeksi->Nomor,
                'NamaAset' => $inspeksi->aset?->Nama,
                'Lokasi' => $inspeksi->aset?->lokasi?->Nama,
                'DijadwalkanPada' => $inspeksi->DijadwalkanPada?->toIso8601String(),
            ])
            ->all());
    }

    /** Unit kerja pengguna untuk chip lokasi di hero; kosong bila tidak ditetapkan. */
    private function lokasiSaya(Pengguna $pengguna): ?string
    {
        if ($pengguna->UnitOrganisasiId === null) {
            return null;
        }

        $nama = DB::table('UnitOrganisasi')
            ->where('OrganisasiId', $pengguna->OrganisasiId)
            ->where('Id', $pengguna->UnitOrganisasiId)
            ->value('Nama');

        return is_string($nama) ? $nama : null;
    }
}
