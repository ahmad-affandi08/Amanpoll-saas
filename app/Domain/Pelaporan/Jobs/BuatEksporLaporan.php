<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Jobs;

use App\Core\Organisasi\KalenderOrganisasi;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Pelaporan\Application\Services\LayananEksporLaporan;
use App\Domain\Pelaporan\Application\Services\PenjagaFilterMetrik;
use App\Domain\Pelaporan\Domain\ValueObjects\FilterMetrik;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Infrastructure\Ekspor\FormatEkspor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

/** Ekspor laporan di antrean (21.05). */
final class BuatEksporLaporan implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Satu percobaan ulang untuk kegagalan sesaat. Proses yang dibunuh hosting
     * tanpa sempat menandai job juga menghabiskan satu percobaan saat diambil
     * ulang, jadi batas ini yang menjamin ekspor berat tidak berulang tanpa akhir.
     */
    public int $tries = 2;

    /**
     * Ekspor besar gagal biasanya karena sumber daya sesaat; jeda memberi ruang pulih.
     *
     * @var list<int>
     */
    public array $backoff = [60];

    /**
     * Harus di bawah --timeout pekerja low dan `retry_after` koneksi
     * `database-panjang` (config/queue.php); keduanya dijaga PekerjaAntreanCronTest.
     */
    public int $timeout = 300;

    /** Ekspor yang habis waktu akan habis waktu lagi; mengulangnya hanya membakar lima menit berikutnya. */
    public bool $failOnTimeout = true;

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
        $this->onConnection(Config::string('queue.koneksi_panjang'));
        $this->onQueue('low');
    }

    public function handle(
        LayananEksporLaporan $layanan,
        KonteksOrganisasi $konteks,
        KalenderOrganisasi $kalender,
        PenjagaFilterMetrik $penjagaFilter,
    ): void {
        $pengguna = Pengguna::query()->withoutGlobalScopes()->find($this->penggunaId);
        if ($pengguna === null || $pengguna->Status !== 'Aktif') {
            return;
        }

        $konteks->tetapkan($pengguna->OrganisasiId);
        Auth::setUser($pengguna);

        $layanan->jalankan(
            $pengguna,
            $this->kunciKpi,
            $penjagaFilter->bersihkan(FilterMetrik::dariArray($this->filter, $kalender->zona($pengguna->OrganisasiId))),
            FormatEkspor::from($this->format),
            $this->judul,
        );
    }
}
