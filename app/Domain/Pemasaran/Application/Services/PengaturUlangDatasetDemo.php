<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Pemasaran\Domain\Contracts\DatasetDemo;
use App\Domain\Pemasaran\Domain\Enums\StatusSesiDemo;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\DemoPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\SesiDemo;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/** Membangun ulang isi tenant demo dari datasetnya (MARKETING.md 11, 29). */
final class PengaturUlangDatasetDemo
{
    public function __construct(
        private readonly RegistriDatasetDemo $registri,
        private readonly TransaksiDatabase $transaksi,
        private readonly KonteksOrganisasi $konteks,
    ) {}

    /** Apakah interval resetnya sudah lewat; job terjadwal memakai ini agar tidak menghapus terlalu sering. */
    public function sudahWaktunya(DemoPemasaran $demo, ?CarbonImmutable $pada = null): bool
    {
        $sekarang = $pada ?? CarbonImmutable::now();

        return $demo->TerakhirResetPada === null
            || $demo->TerakhirResetPada->addMinutes($demo->ResetIntervalMenit)->lessThanOrEqualTo($sekarang);
    }

    public function jalankan(DemoPemasaran $demo, ?CarbonImmutable $pada = null): void
    {
        $dataset = $this->registri->ambil($demo->Dataset);
        $organisasi = $this->tenantDemo($demo);

        // Sesi yang sedang berjalan menunjuk data yang sebentar lagi lenyap; ditutup lebih dulu.
        $this->tutupSesiBerjalan($demo, $pada ?? CarbonImmutable::now());

        // Konteks dikosongkan karena reset menyapu tabel bertenant lewat kueri mentah yang menyebut OrganisasiId sendiri.
        $semula = $this->konteks->id();
        $this->konteks->bersihkan();

        try {
            $this->transaksi->jalankan(function () use ($dataset, $organisasi, $demo, $pada): void {
                $this->kosongkan($dataset, $organisasi->Id);
                $dataset->bangun($organisasi->Id);

                $demo->TerakhirResetPada = $pada ?? CarbonImmutable::now();
                $demo->save();
            });
        } finally {
            $this->konteks->tetapkan($semula);
        }
    }

    /** Penjaga utama: kolom Demo di Organisasi dan tautan di DemoPemasaran harus sepakat lebih dulu. */
    private function tenantDemo(DemoPemasaran $demo): Organisasi
    {
        // Salah tunjuk di satu tempat saja tidak cukup untuk menyentuh tenant sungguhan.
        if ($demo->OrganisasiDemoId === null) {
            throw new AturanBisnisDilanggar(
                "Demo {$demo->Kode} belum menunjuk tenant demo, jadi tidak ada yang boleh direset.",
            );
        }

        $organisasi = Organisasi::query()->whereKey($demo->OrganisasiDemoId)->first();

        if ($organisasi === null) {
            throw new AturanBisnisDilanggar("Tenant demo {$demo->OrganisasiDemoId} tidak ditemukan.");
        }

        if ($organisasi->Demo !== true) {
            throw new AturanBisnisDilanggar(
                "Organisasi {$organisasi->Kode} tidak ditandai sebagai tenant demo; reset dibatalkan.",
            );
        }

        return $organisasi;
    }

    private function kosongkan(DatasetDemo $dataset, string $organisasiId): void
    {
        foreach ($dataset->tabel() as $tabel) {
            DB::table($tabel)->where('OrganisasiId', $organisasiId)->delete();
        }
    }

    private function tutupSesiBerjalan(DemoPemasaran $demo, CarbonImmutable $pada): void
    {
        SesiDemo::query()
            ->where('DemoPemasaranId', $demo->Id)
            ->where('Status', StatusSesiDemo::Berjalan->value)
            ->update([
                'Status' => StatusSesiDemo::Dihentikan->value,
                'SelesaiPada' => $pada,
                'AlasanSelesai' => 'Dataset demo direset.',
            ]);
    }
}
