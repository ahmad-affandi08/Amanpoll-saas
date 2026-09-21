<?php

declare(strict_types=1);

namespace App\Domain\Kontrak\Application\Services;

use App\Core\Konfigurasi\LayananKonfigurasi;
use App\Domain\Kontrak\Infrastructure\Persistence\Models\Kontrak;
use App\Domain\Notifikasi\Application\Services\LayananNotifikasi;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Peringatan kontrak akan berakhir dan kontrak kedaluwarsa (17.04). Ambang hari
 * dapat diatur per organisasi lewat konfigurasi `Kontrak.HariPeringatan`,
 * dengan `PeringatanHariSebelum` pada kontrak sebagai ambang tambahan.
 */
final class LayananPeringatanKontrak
{
    private const IZIN = 'Kontrak.Kelola';

    public function __construct(
        private readonly LayananNotifikasi $layananNotifikasi,
        private readonly LayananKonfigurasi $layananKonfigurasi,
    ) {}

    /**
     * @return array{total: int, aktif: int, akanBerakhir: int, kedaluwarsa: int, tanpaPenyedia: int}
     */
    public function ringkasan(string $organisasiId, ?CarbonImmutable $hariIni = null): array
    {
        $hariIni ??= CarbonImmutable::today();
        $ambangTerjauh = max($this->ambangHari($organisasiId));

        $daftar = Kontrak::query()->where('OrganisasiId', $organisasiId)->get();
        $akanBerakhir = 0;
        $kedaluwarsa = 0;
        $aktif = 0;

        foreach ($daftar as $kontrak) {
            if ($kontrak->Status !== Kontrak::STATUS_AKTIF) {
                continue;
            }
            $aktif++;
            $sisa = $this->sisaHari($kontrak, $hariIni);
            if ($sisa < 0) {
                $kedaluwarsa++;
            } elseif ($sisa <= max($ambangTerjauh, (int) $kontrak->PeringatanHariSebelum)) {
                $akanBerakhir++;
            }
        }

        return [
            'total' => $daftar->count(),
            'aktif' => $aktif,
            'akanBerakhir' => $akanBerakhir,
            'kedaluwarsa' => $kedaluwarsa,
            'tanpaPenyedia' => $daftar->whereNull('PenyediaId')->count(),
        ];
    }

    /**
     * Menandai kontrak yang sudah lewat masa berlaku dan mengirim peringatan.
     *
     * @return array{akanBerakhir: int, kedaluwarsa: int, ditutup: int, dilewati: int}
     */
    public function kirimPeringatan(string $organisasiId, ?CarbonImmutable $hariIni = null): array
    {
        $hariIni ??= CarbonImmutable::today();
        $hasil = ['akanBerakhir' => 0, 'kedaluwarsa' => 0, 'ditutup' => 0, 'dilewati' => 0];

        $penerima = $this->penggunaBerizin($organisasiId);
        $ambang = $this->ambangHari($organisasiId);

        $daftar = Kontrak::query()
            ->with('penyedia')
            ->where('OrganisasiId', $organisasiId)
            ->where('Status', Kontrak::STATUS_AKTIF)
            ->get();

        foreach ($daftar as $kontrak) {
            $sisa = $this->sisaHari($kontrak, $hariIni);
            $namaPenyedia = $kontrak->PenyediaId === null ? 'tanpa penyedia' : (string) $kontrak->penyedia?->Nama;

            if ($sisa < 0) {
                $kontrak->Status = Kontrak::STATUS_BERAKHIR;
                $kontrak->save();
                $hasil['ditutup']++;

                $terkirim = $this->kirimSekaliPerSiklus(
                    $organisasiId,
                    $kontrak,
                    'Kontrak.Kedaluwarsa',
                    'Kontrak berakhir',
                    "Kontrak {$kontrak->Nomor} ({$namaPenyedia}) berakhir pada {$kontrak->BerakhirPada->format('d/m/Y')} dan statusnya otomatis ditutup.",
                    $penerima,
                    CarbonImmutable::parse((string) $kontrak->BerakhirPada),
                );
                $terkirim ? $hasil['kedaluwarsa']++ : $hasil['dilewati']++;

                continue;
            }

            $ambangKontrak = $this->ambangTerpicu($sisa, $ambang, (int) $kontrak->PeringatanHariSebelum);
            if ($ambangKontrak === null) {
                continue;
            }

            $terkirim = $this->kirimSekaliPerSiklus(
                $organisasiId,
                $kontrak,
                "Kontrak.AkanBerakhir.H{$ambangKontrak}",
                'Kontrak akan berakhir',
                "Kontrak {$kontrak->Nomor} ({$namaPenyedia}) berakhir dalam {$sisa} hari pada {$kontrak->BerakhirPada->format('d/m/Y')}.",
                $penerima,
                CarbonImmutable::parse((string) $kontrak->BerakhirPada)->subDays($ambangKontrak),
            );
            $terkirim ? $hasil['akanBerakhir']++ : $hasil['dilewati']++;
        }

        return $hasil;
    }

    /**
     * Ambang dianggap terpicu tepat pada harinya supaya H-90, H-60, dan H-30
     * tidak berbunyi bersamaan setiap hari menjelang berakhir.
     *
     * @param  non-empty-list<int>  $ambang
     */
    private function ambangTerpicu(int $sisa, array $ambang, int $ambangKontrak): ?int
    {
        $semua = array_unique(array_merge($ambang, [$ambangKontrak]));
        rsort($semua);

        foreach ($semua as $batas) {
            if ($sisa === $batas) {
                return $batas;
            }
        }

        return null;
    }

    /**
     * Satu peristiwa hanya dikirim sekali per siklus kontrak. Pembatasnya adalah
     * tanggal pemicu yang diturunkan dari tanggal berakhir, bukan tanggal baris
     * dibuat, supaya kontrak yang diperpanjang tetap memicu peringatan baru.
     *
     * @param  list<string>  $penerima
     */
    private function kirimSekaliPerSiklus(
        string $organisasiId,
        Kontrak $kontrak,
        string $jenisPeristiwa,
        string $judul,
        string $isi,
        array $penerima,
        CarbonImmutable $sejak,
    ): bool {
        $sudahAda = DB::table('Notifikasi')
            ->where('OrganisasiId', $organisasiId)
            ->where('JenisEntitas', 'Kontrak')
            ->where('EntitasId', $kontrak->Id)
            ->where('JenisPeristiwa', $jenisPeristiwa)
            ->where('JadwalKirimPada', '>=', $sejak->startOfDay())
            ->exists();

        if ($sudahAda || $penerima === []) {
            return false;
        }

        foreach ($penerima as $penggunaId) {
            $this->layananNotifikasi->kirim(
                penggunaId: $penggunaId,
                jenisPeristiwa: $jenisPeristiwa,
                isi: $isi,
                judul: $judul,
                jenisEntitas: 'Kontrak',
                entitasId: $kontrak->Id,
            );
        }

        return true;
    }

    private function sisaHari(Kontrak $kontrak, CarbonImmutable $hariIni): int
    {
        return (int) $hariIni->diffInDays(CarbonImmutable::parse((string) $kontrak->BerakhirPada), false);
    }

    /** @return non-empty-list<int> */
    private function ambangHari(string $organisasiId): array
    {
        $mentah = (string) ($this->layananKonfigurasi->ambil($organisasiId, 'Kontrak.HariPeringatan') ?? '90,60,30');
        $ambang = [];
        foreach (explode(',', $mentah) as $bagian) {
            $angka = (int) trim($bagian);
            if ($angka > 0) {
                $ambang[] = $angka;
            }
        }

        return $ambang === [] ? [30] : array_values(array_unique($ambang));
    }

    /** @return list<string> */
    private function penggunaBerizin(string $organisasiId): array
    {
        $penggunaId = DB::table('PenggunaPeran as pp')
            ->join('PeranIzin as pi', 'pi.PeranId', '=', 'pp.PeranId')
            ->join('Izin as i', 'i.Id', '=', 'pi.IzinId')
            ->where('pp.OrganisasiId', $organisasiId)
            ->where('i.Kode', self::IZIN)
            ->where(fn ($q) => $q->whereNull('pp.BerlakuMulai')->orWhere('pp.BerlakuMulai', '<=', now()))
            ->where(fn ($q) => $q->whereNull('pp.BerlakuSampai')->orWhere('pp.BerlakuSampai', '>=', now()))
            ->distinct()
            ->pluck('pp.PenggunaId');

        return array_values(array_map(strval(...), $penggunaId->all()));
    }
}
