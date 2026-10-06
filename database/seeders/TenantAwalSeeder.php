<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Application\Actions\PasangPeranAwal;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Satu tenant kosong dengan satu admin pemilik, untuk uji coba calon klien di produksi.
 *
 * Idempotent: organisasi atau admin yang sudah ada dilewati utuh, sehingga kata sandi yang
 * sudah diganti tidak dikembalikan ke nilai bawaan oleh `db:seed` berikutnya. Tanpa langganan,
 * organisasi berjalan dalam masa uji coba dengan seluruh fitur menyala.
 */
class TenantAwalSeeder extends Seeder
{
    private const KODE_PERAN_PEMILIK = 'PEMILIK';

    public function run(KonteksOrganisasi $konteks, PasangPeranAwal $pasangPeranAwal): void
    {
        /** @var array{kode: string, nama: string, nama_admin: string, email: string, kata_sandi: string} $setelan */
        $setelan = config('amanpoll.tenant_awal');

        // Tenant baru ditulis tanpa konteks tenant mana pun; ScopeOrganisasi menolak yang sebaliknya.
        $konteksSemula = $konteks->id();
        $konteks->bersihkan();

        try {
            DB::transaction(function () use ($setelan, $pasangPeranAwal): void {
                $organisasi = Organisasi::query()->withoutGlobalScopes()->where('Kode', $setelan['kode'])->first()
                    ?? Organisasi::create(['Kode' => $setelan['kode'], 'Nama' => $setelan['nama'], 'Status' => 'Aktif']);

                $this->semaiAdmin($organisasi, $setelan);
                $pasangPeranAwal->jalankan($organisasi->Id);
            });
        } finally {
            $konteks->tetapkan($konteksSemula);
        }

        $this->command?->info("Tenant awal siap: {$setelan['email']} (organisasi {$setelan['kode']}).");
    }

    /** @param array{nama_admin: string, email: string, kata_sandi: string} $setelan */
    private function semaiAdmin(Organisasi $organisasi, array $setelan): void
    {
        $pengguna = Pengguna::query()
            ->withoutGlobalScopes()
            ->where('OrganisasiId', $organisasi->Id)
            ->where('Email', $setelan['email'])
            ->first()
            ?? Pengguna::create([
                'OrganisasiId' => $organisasi->Id,
                'Nama' => $setelan['nama_admin'],
                'Email' => $setelan['email'],
                'KataSandi' => $setelan['kata_sandi'],
                'Status' => 'Aktif',
            ]);

        $peran = $this->peranPemilik($organisasi);

        PenggunaPeran::query()->withoutGlobalScopes()->firstOrCreate([
            'OrganisasiId' => $organisasi->Id,
            'PenggunaId' => $pengguna->Id,
            'PeranId' => $peran->Id,
        ]);
    }

    /** Pemilik memegang seluruh izin; izin yang baru lahir sesudahnya ikut dipasang pada penjalanan berikutnya. */
    private function peranPemilik(Organisasi $organisasi): Peran
    {
        $peran = Peran::query()
            ->withoutGlobalScopes()
            ->where('OrganisasiId', $organisasi->Id)
            ->where('Kode', self::KODE_PERAN_PEMILIK)
            ->first()
            ?? Peran::create([
                'OrganisasiId' => $organisasi->Id,
                'Kode' => self::KODE_PERAN_PEMILIK,
                'Nama' => 'Pemilik',
                'Keterangan' => 'Dibuat oleh TenantAwalSeeder.',
                'BawaanSistem' => true,
            ]);

        foreach (Izin::query()->pluck('Id') as $izinId) {
            PeranIzin::query()->withoutGlobalScopes()->firstOrCreate(['PeranId' => $peran->Id, 'IzinId' => $izinId]);
        }

        return $peran;
    }
}
