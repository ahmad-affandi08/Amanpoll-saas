<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PerangkatPengguna;
use App\Domain\Sinkronisasi\Application\Services\LayananAntrianSinkronisasi;
use App\Domain\Sinkronisasi\Domain\Enums\StatusAntrianSinkronisasi;
use App\Domain\Sinkronisasi\Infrastructure\Persistence\Models\AntrianSinkronisasi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

/** Menjalankan ulang mutasi offline yang masih menunggu (20.03). */
final class ProsesAntrianSinkronisasi extends Command
{
    protected $signature = 'sinkronisasi:proses-antrian {--batas=100 : Jumlah mutasi maksimum per jalan}';

    protected $description = 'Terapkan mutasi offline yang masih menunggu di antrean sinkronisasi';

    public function handle(LayananAntrianSinkronisasi $layanan, KonteksOrganisasi $konteks): int
    {
        $dipulihkan = $layanan->pulihkanTerhenti();

        $antrean = AntrianSinkronisasi::query()
            ->withoutGlobalScopes()
            ->where('Status', StatusAntrianSinkronisasi::Menunggu->value)
            ->orderBy('DiterimaPada')
            ->orderBy('Id')
            ->limit((int) $this->option('batas'))
            ->get();

        $organisasiSemula = $konteks->id();
        $penggunaSemula = Auth::user();
        $selesai = 0;
        $konflik = 0;
        $tertunda = 0;

        foreach ($antrean as $antrian) {
            $pengguna = $this->pemilik($antrian);
            if ($pengguna === null) {
                continue;
            }

            $konteks->tetapkan($antrian->OrganisasiId);
            Auth::setUser($pengguna);

            $hasil = $layanan->proses($antrian, $pengguna);
            match ($hasil->Status) {
                StatusAntrianSinkronisasi::Selesai->value => $selesai++,
                StatusAntrianSinkronisasi::Konflik->value => $konflik++,
                default => $tertunda++,
            };
        }

        $konteks->tetapkan($organisasiSemula);
        if ($penggunaSemula !== null) {
            Auth::setUser($penggunaSemula);
        }

        $this->info("Antrean sinkronisasi diproses. Selesai: {$selesai}, konflik: {$konflik}, belum tuntas: {$tertunda}, dipulihkan: {$dipulihkan}.");

        return self::SUCCESS;
    }

    private function pemilik(AntrianSinkronisasi $antrian): ?Pengguna
    {
        $perangkat = PerangkatPengguna::query()
            ->withoutGlobalScopes()
            ->find($antrian->PerangkatPenggunaId);

        if ($perangkat === null) {
            return null;
        }

        return Pengguna::query()
            ->withoutGlobalScopes()
            ->where('Status', 'Aktif')
            ->find($perangkat->PenggunaId);
    }
}
