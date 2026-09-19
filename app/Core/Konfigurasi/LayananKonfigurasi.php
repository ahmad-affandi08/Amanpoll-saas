<?php

declare(strict_types=1);

namespace App\Core\Konfigurasi;

use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\DB;

final class LayananKonfigurasi
{
    public function __construct(private readonly CacheRepository $cache) {}

    public function ambil(string $organisasiId, string $kunci): mixed
    {
        $definisi = DefinisiKonfigurasi::cari($kunci);
        if ($definisi === null) {
            throw new DataTidakDitemukan("Kunci konfigurasi '{$kunci}' tidak dikenal.");
        }

        return $this->cache->remember(
            $this->kunciCache($organisasiId, $kunci),
            now()->addHour(),
            function () use ($organisasiId, $kunci, $definisi): mixed {
                $baris = DB::table('KonfigurasiOrganisasi')
                    ->where('OrganisasiId', $organisasiId)
                    ->where('Kunci', $kunci)
                    ->first(['Nilai']);

                return $baris ? json_decode((string) $baris->Nilai, true) : $definisi['Default'];
            },
        );
    }

    /**
     * @return list<array{Kunci: string, Namespace: string, Tipe: string, Label: string, Rahasia: bool, Nilai: mixed}>
     */
    public function semua(string $organisasiId): array
    {
        return array_map(
            fn (string $kunci, array $definisi): array => [
                'Kunci' => $kunci,
                'Namespace' => $definisi['Namespace'],
                'Tipe' => $definisi['Tipe'],
                'Label' => $definisi['Label'],
                'Rahasia' => $definisi['Rahasia'],
                'Nilai' => $definisi['Rahasia'] ? null : $this->ambil($organisasiId, $kunci),
            ],
            array_keys(DefinisiKonfigurasi::daftar()),
            DefinisiKonfigurasi::daftar(),
        );
    }

    public function bersihkanCache(string $organisasiId, string $kunci): void
    {
        $this->cache->forget($this->kunciCache($organisasiId, $kunci));
    }

    private function kunciCache(string $organisasiId, string $kunci): string
    {
        return "konfigurasi:{$organisasiId}:{$kunci}";
    }
}
