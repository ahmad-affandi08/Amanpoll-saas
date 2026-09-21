<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Application\Services;

use App\Core\Konfigurasi\LayananKonfigurasi;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\KepatuhanAset;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\SertifikasiAset;
use App\Domain\Notifikasi\Application\Services\LayananNotifikasi;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Peringatan masa berlaku kepatuhan aset dan sertifikat (18.03, 18.04).
 * Ambang hari memakai konfigurasi organisasi yang sama dengan kontrak agar
 * tidak ada dua sumber kebenaran untuk kebijakan pengingat.
 */
final class LayananKepatuhan
{
    private const IZIN = 'Kepatuhan.Kelola';

    public function __construct(
        private readonly LayananNotifikasi $layananNotifikasi,
        private readonly LayananKonfigurasi $layananKonfigurasi,
    ) {}

    /**
     * @return array{
     *   totalKewajiban: int,
     *   patuh: int,
     *   tidakPatuh: int,
     *   belumDiperiksa: int,
     *   kedaluwarsa: int,
     *   persentaseKepatuhan: float,
     *   sertifikatAktif: int,
     *   sertifikatAkanBerakhir: int,
     *   sertifikatKedaluwarsa: int
     * }
     */
    public function ringkasan(string $organisasiId, ?CarbonImmutable $hariIni = null): array
    {
        $hariIni ??= CarbonImmutable::today();
        $ambangTerjauh = max($this->ambangHari($organisasiId));

        $kewajiban = KepatuhanAset::query()->where('OrganisasiId', $organisasiId)->get();
        $patuh = 0;
        $tidakPatuh = 0;
        $belum = 0;
        $kedaluwarsa = 0;

        foreach ($kewajiban as $baris) {
            $status = $this->statusEfektif($baris, $hariIni);
            match ($status) {
                KepatuhanAset::STATUS_PATUH => $patuh++,
                KepatuhanAset::STATUS_TIDAK_PATUH => $tidakPatuh++,
                KepatuhanAset::STATUS_KEDALUWARSA => $kedaluwarsa++,
                default => $belum++,
            };
        }

        $total = $kewajiban->count();
        $sertifikat = SertifikasiAset::query()->where('OrganisasiId', $organisasiId)->get();
        $sertifikatAkanBerakhir = 0;
        $sertifikatKedaluwarsa = 0;
        $sertifikatAktif = 0;

        foreach ($sertifikat as $baris) {
            if ($baris->Status === SertifikasiAset::STATUS_DICABUT) {
                continue;
            }
            $sisa = $this->sisaHari($baris->BerlakuSampai, $hariIni);
            if ($sisa === null) {
                $sertifikatAktif++;

                continue;
            }
            if ($sisa < 0) {
                $sertifikatKedaluwarsa++;
            } elseif ($sisa <= $ambangTerjauh) {
                $sertifikatAkanBerakhir++;
                $sertifikatAktif++;
            } else {
                $sertifikatAktif++;
            }
        }

        return [
            'totalKewajiban' => $total,
            'patuh' => $patuh,
            'tidakPatuh' => $tidakPatuh,
            'belumDiperiksa' => $belum,
            'kedaluwarsa' => $kedaluwarsa,
            'persentaseKepatuhan' => $total > 0 ? round(($patuh / $total) * 100, 1) : 100.0,
            'sertifikatAktif' => $sertifikatAktif,
            'sertifikatAkanBerakhir' => $sertifikatAkanBerakhir,
            'sertifikatKedaluwarsa' => $sertifikatKedaluwarsa,
        ];
    }

    /**
     * Status kepatuhan yang sudah memperhitungkan masa berlaku, tanpa mengubah
     * baris di database. Dipakai untuk tampilan agar angka tidak menunggu cron.
     */
    public function statusEfektif(KepatuhanAset $kepatuhan, ?CarbonImmutable $hariIni = null): string
    {
        $hariIni ??= CarbonImmutable::today();
        $sisa = $this->sisaHari($kepatuhan->BerlakuSampai, $hariIni);

        if ($kepatuhan->Status === KepatuhanAset::STATUS_PATUH && $sisa !== null && $sisa < 0) {
            return KepatuhanAset::STATUS_KEDALUWARSA;
        }

        return $kepatuhan->Status;
    }

    /**
     * @return array{kepatuhanKedaluwarsa: int, kepatuhanAkanBerakhir: int, sertifikatKedaluwarsa: int, sertifikatAkanBerakhir: int, dilewati: int}
     */
    public function kirimPeringatan(string $organisasiId, ?CarbonImmutable $hariIni = null): array
    {
        $hariIni ??= CarbonImmutable::today();
        $hasil = [
            'kepatuhanKedaluwarsa' => 0,
            'kepatuhanAkanBerakhir' => 0,
            'sertifikatKedaluwarsa' => 0,
            'sertifikatAkanBerakhir' => 0,
            'dilewati' => 0,
        ];

        $penerima = $this->penggunaBerizin($organisasiId);
        $ambang = $this->ambangHari($organisasiId);

        foreach ($this->kewajibanBerbatasWaktu($organisasiId) as $kepatuhan) {
            $sisa = $this->sisaHari($kepatuhan->BerlakuSampai, $hariIni);
            if ($sisa === null) {
                continue;
            }
            $aset = $kepatuhan->aset;
            $persyaratan = $kepatuhan->persyaratanKepatuhan;
            $label = "{$persyaratan?->Kode} — {$persyaratan?->Nama} pada aset {$aset?->KodeAset}";

            if ($sisa < 0) {
                if ($kepatuhan->Status !== KepatuhanAset::STATUS_KEDALUWARSA) {
                    $kepatuhan->Status = KepatuhanAset::STATUS_KEDALUWARSA;
                    $kepatuhan->save();
                }

                $terkirim = $this->kirimSekaliPerSiklus(
                    $organisasiId,
                    'KepatuhanAset',
                    $kepatuhan->Id,
                    'Kepatuhan.Kedaluwarsa',
                    'Kepatuhan aset kedaluwarsa',
                    "Masa berlaku kepatuhan {$label} sudah lewat sejak {$kepatuhan->BerlakuSampai?->format('d/m/Y')}.",
                    $penerima,
                    CarbonImmutable::parse((string) $kepatuhan->BerlakuSampai),
                );
                $terkirim ? $hasil['kepatuhanKedaluwarsa']++ : $hasil['dilewati']++;

                continue;
            }

            $batas = $this->ambangTerpicu($sisa, $ambang);
            if ($batas === null) {
                continue;
            }

            $terkirim = $this->kirimSekaliPerSiklus(
                $organisasiId,
                'KepatuhanAset',
                $kepatuhan->Id,
                "Kepatuhan.AkanBerakhir.H{$batas}",
                'Kepatuhan aset akan jatuh tempo',
                "Kepatuhan {$label} perlu diperiksa ulang dalam {$sisa} hari ({$kepatuhan->BerlakuSampai?->format('d/m/Y')}).",
                $penerima,
                CarbonImmutable::parse((string) $kepatuhan->BerlakuSampai)->subDays($batas),
            );
            $terkirim ? $hasil['kepatuhanAkanBerakhir']++ : $hasil['dilewati']++;
        }

        foreach ($this->sertifikatBerbatasWaktu($organisasiId) as $sertifikat) {
            $sisa = $this->sisaHari($sertifikat->BerlakuSampai, $hariIni);
            if ($sisa === null) {
                continue;
            }
            $label = "{$sertifikat->JenisSertifikasi} ({$sertifikat->NomorSertifikat}) pada aset {$sertifikat->aset?->KodeAset}";

            if ($sisa < 0) {
                if ($sertifikat->Status !== SertifikasiAset::STATUS_KEDALUWARSA) {
                    $sertifikat->Status = SertifikasiAset::STATUS_KEDALUWARSA;
                    $sertifikat->save();
                }

                $terkirim = $this->kirimSekaliPerSiklus(
                    $organisasiId,
                    'SertifikasiAset',
                    $sertifikat->Id,
                    'Sertifikasi.Kedaluwarsa',
                    'Sertifikat aset kedaluwarsa',
                    "Sertifikat {$label} berakhir pada {$sertifikat->BerlakuSampai?->format('d/m/Y')}.",
                    $penerima,
                    CarbonImmutable::parse((string) $sertifikat->BerlakuSampai),
                );
                $terkirim ? $hasil['sertifikatKedaluwarsa']++ : $hasil['dilewati']++;

                continue;
            }

            $batas = $this->ambangTerpicu($sisa, $ambang);
            if ($batas === null) {
                continue;
            }

            $terkirim = $this->kirimSekaliPerSiklus(
                $organisasiId,
                'SertifikasiAset',
                $sertifikat->Id,
                "Sertifikasi.AkanBerakhir.H{$batas}",
                'Sertifikat aset akan berakhir',
                "Sertifikat {$label} berakhir dalam {$sisa} hari ({$sertifikat->BerlakuSampai?->format('d/m/Y')}).",
                $penerima,
                CarbonImmutable::parse((string) $sertifikat->BerlakuSampai)->subDays($batas),
            );
            $terkirim ? $hasil['sertifikatAkanBerakhir']++ : $hasil['dilewati']++;
        }

        return $hasil;
    }

    /**
     * @return Collection<int, KepatuhanAset>
     */
    private function kewajibanBerbatasWaktu(string $organisasiId): Collection
    {
        return KepatuhanAset::query()
            ->with(['aset', 'persyaratanKepatuhan'])
            ->where('OrganisasiId', $organisasiId)
            ->whereNotNull('BerlakuSampai')
            ->whereIn('Status', [KepatuhanAset::STATUS_PATUH, KepatuhanAset::STATUS_KEDALUWARSA])
            ->get();
    }

    /**
     * @return Collection<int, SertifikasiAset>
     */
    private function sertifikatBerbatasWaktu(string $organisasiId): Collection
    {
        return SertifikasiAset::query()
            ->with('aset')
            ->where('OrganisasiId', $organisasiId)
            ->whereNotNull('BerlakuSampai')
            ->whereIn('Status', [SertifikasiAset::STATUS_AKTIF, SertifikasiAset::STATUS_KEDALUWARSA])
            ->get();
    }

    /** @param non-empty-list<int> $ambang */
    private function ambangTerpicu(int $sisa, array $ambang): ?int
    {
        foreach ($ambang as $batas) {
            if ($sisa === $batas) {
                return $batas;
            }
        }

        return null;
    }

    /**
     * Sama seperti kontrak: pembatas duplikasi adalah tanggal pemicu yang
     * diturunkan dari masa berlaku, sehingga perpanjangan memulai siklus baru.
     *
     * @param  list<string>  $penerima
     */
    private function kirimSekaliPerSiklus(
        string $organisasiId,
        string $jenisEntitas,
        string $entitasId,
        string $jenisPeristiwa,
        string $judul,
        string $isi,
        array $penerima,
        CarbonImmutable $sejak,
    ): bool {
        $sudahAda = DB::table('Notifikasi')
            ->where('OrganisasiId', $organisasiId)
            ->where('JenisEntitas', $jenisEntitas)
            ->where('EntitasId', $entitasId)
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
                jenisEntitas: $jenisEntitas,
                entitasId: $entitasId,
            );
        }

        return true;
    }

    private function sisaHari(mixed $berlakuSampai, CarbonImmutable $hariIni): ?int
    {
        if ($berlakuSampai === null) {
            return null;
        }

        return (int) $hariIni->diffInDays(CarbonImmutable::parse((string) $berlakuSampai), false);
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
