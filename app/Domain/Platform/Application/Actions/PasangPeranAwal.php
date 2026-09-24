<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Domain\ValueObjects\KatalogPeranAwal;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/**
 * Memasang peran bawaan KatalogPeranAwal ke satu organisasi.
 *
 * Idempotent: peran yang kodenya sudah ada dilewati utuh, izinnya tidak
 * disentuh. Tenant yang sudah menyesuaikan "Teknisi" miliknya tidak boleh
 * kehilangan penyesuaian itu hanya karena tombol pasang ditekan dua kali.
 *
 * Peran yang lahir di sini sengaja tidak ditandai BawaanSistem, berbeda dengan
 * peran Pemilik. Ini titik mulai, bukan pagar: tenant harus tetap bisa
 * mengubah izinnya, mengganti namanya, atau menghapusnya.
 *
 * Penanda `TampilanLapangan` ikut dipasang dari katalog, sehingga pemegang
 * Teknisi dan Pelapor yang baru langsung masuk Mode Lapangan (PRD 8.20).
 */
final class PasangPeranAwal
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly KonteksOrganisasi $konteks,
    ) {}

    /**
     * @return list<string> Kode peran yang baru dipasang; kosong bila sudah lengkap.
     */
    public function jalankan(string $organisasiId): array
    {
        if ($this->konteks->ada() && $this->konteks->wajibId() !== $organisasiId) {
            throw new AturanBisnisDilanggar('Peran bawaan tidak boleh dipasang ke organisasi lain.');
        }

        $izinPerKode = Izin::query()->pluck('Id', 'Kode');

        // Katalog menyebut izin lewat kodenya supaya terbaca, sedangkan yang
        // ada di organisasi lama belum tentu selengkap katalognya.
        $kodeTerpasang = Peran::query()
            ->withoutGlobalScopes()
            ->where('OrganisasiId', $organisasiId)
            ->pluck('Kode')
            ->all();

        $baru = [];

        foreach (KatalogPeranAwal::semua() as $contoh) {
            if (in_array($contoh['Kode'], $kodeTerpasang, true)) {
                continue;
            }

            $this->transaksi->jalankan(function () use ($contoh, $izinPerKode, $organisasiId): void {
                $peran = Peran::create([
                    'OrganisasiId' => $organisasiId,
                    'Kode' => $contoh['Kode'],
                    'Nama' => $contoh['Nama'],
                    'Keterangan' => $contoh['Keterangan'],
                    'BawaanSistem' => false,
                    'TampilanLapangan' => $contoh['TampilanLapangan'],
                ]);

                foreach ($contoh['Izin'] as $kodeIzin) {
                    $izinId = $izinPerKode[$kodeIzin] ?? null;

                    if ($izinId === null) {
                        continue;
                    }

                    PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $izinId]);
                }
            });

            $baru[] = $contoh['Kode'];
        }

        return $baru;
    }
}
