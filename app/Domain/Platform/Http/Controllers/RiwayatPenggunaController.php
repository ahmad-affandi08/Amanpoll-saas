<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Controllers;

use App\Domain\Aset\Infrastructure\Persistence\Models\RiwayatPenanggungJawabAset;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\CatatanAkses;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\WaktuKerja;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PerangkatPengguna;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Rekam jejak satu pengguna, dibaca tab Beban Kerja dan Aktivitas.
 *
 * Halaman pengguna dulu hanya menampilkan profil dan perannya, sehingga
 * pertanyaan yang justru dibawa penyelia -- orang ini sedang memegang apa,
 * sudah mencatat berapa jam, aset apa yang ditanggungnya -- tidak terjawab
 * di mana pun kecuali dengan menyaring modul lain satu per satu.
 */
final class RiwayatPenggunaController extends Controller
{
    /** Riwayat pengguna lama dapat mencapai ribuan baris; yang ditampilkan dibatasi dan dikatakan jumlahnya. */
    private const MAKS_BARIS = 50;

    public function bebanKerja(Pengguna $pengguna): JsonResponse
    {
        $this->authorize('view', $pengguna);

        $totalPenugasan = $pengguna->penugasanPerintahKerja()->count();
        $totalWaktuKerja = $pengguna->waktuKerja()->count();
        $totalTanggungJawab = $pengguna->riwayatPenanggungJawabAset()->count();

        return response()->json([
            'ringkasan' => [
                'JumlahPenugasan' => $totalPenugasan,
                'JumlahPenugasanBerjalan' => $pengguna->penugasanPerintahKerja()
                    ->whereNull('SelesaiPada')
                    ->count(),
                'TotalMenitKerja' => (int) $pengguna->waktuKerja()->sum('DurasiMenit'),
                'JumlahAsetDitanggung' => $pengguna->riwayatPenanggungJawabAset()
                    ->whereNull('SelesaiPada')
                    ->count(),
            ],
            'penugasan' => [
                'total' => $totalPenugasan,
                'data' => $pengguna->penugasanPerintahKerja()
                    ->with('perintahKerja')
                    ->limit(self::MAKS_BARIS)
                    ->get()
                    ->map(fn (PenugasanPerintahKerja $satu): array => [
                        'Id' => $satu->Id,
                        'PerintahKerjaId' => $satu->PerintahKerjaId,
                        'Nomor' => $satu->perintahKerja?->Nomor,
                        'Judul' => $satu->perintahKerja?->Judul,
                        'StatusPerintahKerja' => $satu->perintahKerja?->Status,
                        'PeranTugas' => $satu->PeranTugas,
                        'Status' => $satu->Status,
                        'DitugaskanPada' => $satu->DitugaskanPada->toIso8601String(),
                        'SelesaiPada' => $satu->SelesaiPada?->toIso8601String(),
                    ])
                    ->all(),
            ],
            'waktuKerja' => [
                'total' => $totalWaktuKerja,
                'data' => $pengguna->waktuKerja()
                    ->with('perintahKerja')
                    ->limit(self::MAKS_BARIS)
                    ->get()
                    ->map(fn (WaktuKerja $satu): array => [
                        'Id' => $satu->Id,
                        'PerintahKerjaId' => $satu->PerintahKerjaId,
                        'Nomor' => $satu->perintahKerja?->Nomor,
                        'JenisWaktu' => $satu->JenisWaktu,
                        'DurasiMenit' => $satu->DurasiMenit,
                        'MulaiPada' => $satu->MulaiPada->toIso8601String(),
                        'SelesaiPada' => $satu->SelesaiPada?->toIso8601String(),
                    ])
                    ->all(),
            ],
            'tanggungJawabAset' => [
                'total' => $totalTanggungJawab,
                'data' => $pengguna->riwayatPenanggungJawabAset()
                    ->with(['aset', 'unitOrganisasi'])
                    ->limit(self::MAKS_BARIS)
                    ->get()
                    ->map(fn (RiwayatPenanggungJawabAset $satu): array => [
                        'Id' => $satu->Id,
                        'AsetId' => $satu->AsetId,
                        'Aset' => $satu->aset?->Nama,
                        'KodeAset' => $satu->aset?->KodeAset,
                        'UnitOrganisasi' => $satu->unitOrganisasi?->Nama,
                        'MulaiPada' => $satu->MulaiPada->toIso8601String(),
                        'SelesaiPada' => $satu->SelesaiPada?->toIso8601String(),
                    ])
                    ->all(),
            ],
        ]);
    }

    public function aktivitas(Pengguna $pengguna): JsonResponse
    {
        $this->authorize('view', $pengguna);

        $akses = $pengguna->catatanAkses();
        $totalAkses = (clone $akses)->count();
        $perangkat = PerangkatPengguna::query()
            ->where('PenggunaId', $pengguna->Id)
            ->orderByDesc('TerakhirSinkronPada');

        return response()->json([
            'ringkasan' => [
                'JumlahAkses' => $totalAkses,
                'JumlahAksesGagal' => (clone $akses)->where('Berhasil', false)->count(),
                'TerakhirMasukPada' => $pengguna->TerakhirMasukPada?->toIso8601String(),
                'JumlahPerangkat' => (clone $perangkat)->count(),
            ],
            'akses' => [
                'total' => $totalAkses,
                'data' => $pengguna->catatanAkses()
                    ->limit(self::MAKS_BARIS)
                    ->get()
                    ->map(fn (CatatanAkses $satu): array => [
                        'Id' => $satu->Id,
                        'Jenis' => $satu->Jenis,
                        'Berhasil' => (bool) $satu->Berhasil,
                        'AlasanGagal' => $satu->AlasanGagal,
                        'AlamatIp' => $satu->AlamatIp,
                        'DibuatPada' => $satu->DibuatPada->toIso8601String(),
                    ])
                    ->all(),
            ],
            'perangkat' => (clone $perangkat)
                ->limit(self::MAKS_BARIS)
                ->get()
                ->map(fn (PerangkatPengguna $satu): array => [
                    'Id' => $satu->Id,
                    'NamaPerangkat' => $satu->NamaPerangkat,
                    'Platform' => $satu->Platform,
                    'Status' => $satu->Status,
                    'TerakhirSinkronPada' => $satu->TerakhirSinkronPada?->toIso8601String(),
                ])
                ->all(),
        ]);
    }
}
