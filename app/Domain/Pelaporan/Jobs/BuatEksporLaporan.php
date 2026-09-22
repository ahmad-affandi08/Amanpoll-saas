<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Jobs;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Pelaporan\Application\Services\LayananEksporLaporan;
use App\Domain\Pelaporan\Domain\Enums\FormatEkspor;
use App\Domain\Pelaporan\Domain\ValueObjects\FilterMetrik;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;

/** Ekspor laporan di antrean (21.05). */
final class BuatEksporLaporan implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    /**
     * @param  list<string>  $kunciKpi
     * @param  array<string, mixed>  $filter
     */
    public function __construct(
        private readonly string $penggunaId,
        private readonly array $kunciKpi,
        private readonly array $filter,
        private readonly string $format,
        private readonly string $judul,
    ) {
        $this->onQueue('low');
    }

    public function handle(LayananEksporLaporan $layanan, KonteksOrganisasi $konteks): void
    {
        $pengguna = Pengguna::query()->withoutGlobalScopes()->find($this->penggunaId);
        if ($pengguna === null || $pengguna->Status !== 'Aktif') {
            return;
        }

        $konteks->tetapkan($pengguna->OrganisasiId);
        Auth::setUser($pengguna);

        $layanan->jalankan(
            $pengguna,
            $this->kunciKpi,
            FilterMetrik::dariArray($this->filter),
            FormatEkspor::from($this->format),
            $this->judul,
        );
    }
}
