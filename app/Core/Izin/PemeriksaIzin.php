<?php

declare(strict_types=1);

namespace App\Core\Izin;

use App\Core\Organisasi\KonteksOrganisasi;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\DB;

final class PemeriksaIzin
{
    public function __construct(
        private readonly CacheRepository $cache,
        private readonly KonteksOrganisasi $konteksOrganisasi,
    ) {}

    public function boleh(string $penggunaId, string $kodeIzin): bool
    {
        return in_array($kodeIzin, $this->daftarKodeIzin($penggunaId), true);
    }

    /**
     * @return list<string>
     */
    public function daftarKodeIzin(string $penggunaId): array
    {
        $organisasiId = $this->konteksOrganisasi->id();
        if (!$organisasiId) {
            return [];
        }

        return $this->cache->remember(
            $this->kunciCache($organisasiId, $penggunaId),
            now()->addMinutes(5),
            fn (): array => $this->ambilKodeIzinDariDatabase($organisasiId, $penggunaId),
        );
    }

    /**
     * Dipanggil setiap kali PenggunaPeran atau PeranIzin berubah, supaya
     * pencabutan izin langsung berlaku tanpa menunggu cache kedaluwarsa.
     */
    public function bersihkanCache(string $organisasiId, string $penggunaId): void
    {
        $this->cache->forget($this->kunciCache($organisasiId, $penggunaId));
    }

    /**
     * @return list<string>
     */
    private function ambilKodeIzinDariDatabase(string $organisasiId, string $penggunaId): array
    {
        $kode = DB::table('PenggunaPeran as pp')
            ->join('PeranIzin as pi', 'pi.PeranId', '=', 'pp.PeranId')
            ->join('Izin as i', 'i.Id', '=', 'pi.IzinId')
            ->where('pp.OrganisasiId', $organisasiId)
            ->where('pp.PenggunaId', $penggunaId)
            ->where(function ($q): void {
                $q->whereNull('pp.BerlakuMulai')->orWhere('pp.BerlakuMulai', '<=', now());
            })
            ->where(function ($q): void {
                $q->whereNull('pp.BerlakuSampai')->orWhere('pp.BerlakuSampai', '>=', now());
            })
            ->distinct()
            ->pluck('i.Kode');

        return array_values(array_map(strval(...), $kode->all()));
    }

    private function kunciCache(string $organisasiId, string $penggunaId): string
    {
        return "izin:{$organisasiId}:{$penggunaId}";
    }
}
