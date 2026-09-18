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
        $organisasiId = $this->konteksOrganisasi->id();
        if (!$organisasiId) return false;

        $key = "izin:{$organisasiId}:{$penggunaId}:{$kodeIzin}";

        return (bool) $this->cache->remember($key, now()->addMinutes(5), function () use ($penggunaId, $kodeIzin, $organisasiId): bool {
            return DB::table('PenggunaPeran as pp')
                ->join('PeranIzin as pi', 'pi.PeranId', '=', 'pp.PeranId')
                ->join('Izin as i', 'i.Id', '=', 'pi.IzinId')
                ->where('pp.OrganisasiId', $organisasiId)
                ->where('pp.PenggunaId', $penggunaId)
                ->where('i.Kode', $kodeIzin)
                ->where(function ($q): void {
                    $q->whereNull('pp.BerlakuMulai')->orWhere('pp.BerlakuMulai', '<=', now());
                })
                ->where(function ($q): void {
                    $q->whereNull('pp.BerlakuSampai')->orWhere('pp.BerlakuSampai', '>=', now());
                })
                ->exists();
        });
    }
}
