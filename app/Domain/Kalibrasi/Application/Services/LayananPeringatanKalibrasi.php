<?php

declare(strict_types=1);

namespace App\Domain\Kalibrasi\Application\Services;

use App\Core\Organisasi\KalenderOrganisasi;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\RencanaKalibrasi;
use App\Domain\Notifikasi\Application\Services\LayananNotifikasi;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class LayananPeringatanKalibrasi
{
    public function __construct(
        private readonly LayananNotifikasi $layananNotifikasi,
        private readonly KalenderOrganisasi $kalender,
    ) {}

    /**
     * Menghitung statistik kepatuhan kalibrasi dalam satu organisasi.
     *
     * @return array{
     *   total: int,
     *   valid: int,
     *   segeraJatuhTempo: int,
     *   terlambat: int,
     *   persentaseKepatuhan: float
     * }
     */
    public function hitungKepatuhan(string $organisasiId): array
    {
        $hariIni = $this->kalender->hariIni($organisasiId);

        $rencanaAktif = RencanaKalibrasi::query()
            ->where('OrganisasiId', $organisasiId)
            ->where('Aktif', true)
            ->get();

        $total = $rencanaAktif->count();
        $valid = 0;
        $segeraJatuhTempo = 0;
        $terlambat = 0;

        foreach ($rencanaAktif as $rk) {
            $tglBerikutnya = Carbon::parse($rk->TanggalBerikutnya);
            $batasPeringatan = (clone $hariIni)->addDays((int) $rk->PeringatanHariSebelum);

            if ($tglBerikutnya->lt($hariIni)) {
                $terlambat++;
            } elseif ($tglBerikutnya->lte($batasPeringatan)) {
                $segeraJatuhTempo++;
            } else {
                $valid++;
            }
        }

        $persentaseKepatuhan = $total > 0
            ? round(($valid / $total) * 100, 1)
            : 100.0;

        return [
            'total' => $total,
            'valid' => $valid,
            'segeraJatuhTempo' => $segeraJatuhTempo,
            'terlambat' => $terlambat,
            'persentaseKepatuhan' => $persentaseKepatuhan,
        ];
    }

    /**
     * Memeriksa dan mengirim notifikasi kalibrasi due-soon & overdue
     * dengan proteksi anti-duplikasi pada hari yang sama (14.05).
     *
     * `diffInDays()` Carbon 3 mengembalikan float bertanda (negatif bila
     * argumennya lebih awal), jadi selisih untuk isi pesan selalu dihitung
     * dari tanggal yang lebih awal ke yang lebih akhir lalu dijadikan int.
     *
     * @return array{segeraJatuhTempo: int, terlambat: int, dilewati: int}
     */
    public function kirimPeringatan(string $organisasiId): array
    {
        $hariIni = $this->kalender->hariIni($organisasiId);
        $hasil = [
            'segeraJatuhTempo' => 0,
            'terlambat' => 0,
            'dilewati' => 0,
        ];

        $penggunaIdBerizin = $this->penggunaBerizinKalibrasiKelola($organisasiId);
        if (empty($penggunaIdBerizin)) {
            return $hasil;
        }

        $daftarRencana = RencanaKalibrasi::query()
            ->with(['aset', 'jenisKalibrasi'])
            ->where('OrganisasiId', $organisasiId)
            ->where('Aktif', true)
            ->get();

        foreach ($daftarRencana as $rencana) {
            $tglBerikutnya = Carbon::parse($rencana->TanggalBerikutnya);
            $batasPeringatan = (clone $hariIni)->addDays((int) $rencana->PeringatanHariSebelum);

            $jenisPeristiwa = null;
            $judul = null;
            $isi = null;

            if ($tglBerikutnya->lt($hariIni)) {
                // Kalibrasi sudah lewat jatuh tempo
                $jenisPeristiwa = 'Kalibrasi.Terlambat';
                $hariTerlambat = (int) $tglBerikutnya->diffInDays($hariIni);
                $judul = 'Kalibrasi Aset Terlambat';
                $isi = "Kalibrasi untuk aset {$rencana->aset?->Nama} ({$rencana->aset?->KodeAset}) telah terlambat {$hariTerlambat} hari (jatuh tempo: {$tglBerikutnya->format('d/m/Y')}).";
            } elseif ($tglBerikutnya->lte($batasPeringatan)) {
                // Kalibrasi segera jatuh tempo
                $jenisPeristiwa = 'Kalibrasi.SegeraJatuhTempo';
                $sisaHari = (int) $hariIni->diffInDays($tglBerikutnya);
                $judul = 'Pengingat Kalibrasi Aset';
                $isi = "Kalibrasi untuk aset {$rencana->aset?->Nama} ({$rencana->aset?->KodeAset}) akan jatuh tempo dalam {$sisaHari} hari ({$tglBerikutnya->format('d/m/Y')}).";
            }

            if ($jenisPeristiwa === null) {
                continue;
            }

            // Anti duplikasi (14.05).
            $sudahAdaNotifikasiHariIni = DB::table('Notifikasi')
                ->where('OrganisasiId', $organisasiId)
                ->where('JenisEntitas', 'RencanaKalibrasi')
                ->where('EntitasId', $rencana->Id)
                ->where('JenisPeristiwa', $jenisPeristiwa)
                // Rentang hari lokal organisasi, bukan whereDate: JadwalKirimPada
                // tersimpan UTC, dan tanggal UTC-nya berbeda dari tanggal lokal
                // selama jam-jam pertama tiap hari.
                ->where('JadwalKirimPada', '>=', $this->kalender->awalHari($hariIni, $organisasiId))
                ->where('JadwalKirimPada', '<', $this->kalender->awalHariBerikutnya($hariIni, $organisasiId))
                ->exists();

            if ($sudahAdaNotifikasiHariIni) {
                $hasil['dilewati']++;

                continue;
            }

            // Kirim notifikasi ke semua personil berizin Kalibrasi.Kelola
            foreach ($penggunaIdBerizin as $penggunaId) {
                $this->layananNotifikasi->kirim(
                    penggunaId: (string) $penggunaId,
                    jenisPeristiwa: $jenisPeristiwa,
                    isi: $isi,
                    judul: $judul,
                    jenisEntitas: 'RencanaKalibrasi',
                    entitasId: (string) $rencana->Id,
                );
            }

            if ($jenisPeristiwa === 'Kalibrasi.Terlambat') {
                $hasil['terlambat']++;
            } else {
                $hasil['segeraJatuhTempo']++;
            }
        }

        return $hasil;
    }

    /**
     * @return list<string>
     */
    private function penggunaBerizinKalibrasiKelola(string $organisasiId): array
    {
        $penggunaId = DB::table('PenggunaPeran as pp')
            ->join('PeranIzin as pi', 'pi.PeranId', '=', 'pp.PeranId')
            ->join('Izin as i', 'i.Id', '=', 'pi.IzinId')
            ->where('pp.OrganisasiId', $organisasiId)
            ->where('i.Kode', 'Kalibrasi.Kelola')
            ->where(fn ($q) => $q->whereNull('pp.BerlakuMulai')->orWhere('pp.BerlakuMulai', '<=', now()))
            ->where(fn ($q) => $q->whereNull('pp.BerlakuSampai')->orWhere('pp.BerlakuSampai', '>=', now()))
            ->distinct()
            ->pluck('pp.PenggunaId');

        return array_values(array_map(strval(...), $penggunaId->all()));
    }
}
