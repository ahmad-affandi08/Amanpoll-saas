<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Pemasaran\Domain\Enums\JenisEventDemo;
use App\Domain\Pemasaran\Domain\Enums\ModulDemo;
use App\Domain\Pemasaran\Domain\Enums\StatusSesiDemo;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\DemoPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\EventDemo;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\SesiDemo;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;

/** Siklus hidup satu sesi demo beserta batas-batasnya (MARKETING.md 11). */
final class LayananSesiDemo
{
    public function __construct(
        private readonly PerekamEventPemasaran $perekam,
        private readonly TransaksiDatabase $transaksi,
    ) {}

    public function mulai(
        DemoPemasaran $demo,
        string $pengenalPengunjung,
        ?string $sesiPengunjungId = null,
    ): SesiDemo {
        if (! $demo->Aktif) {
            throw new AturanBisnisDilanggar("Demo {$demo->Kode} sedang dimatikan.");
        }

        $sekarang = CarbonImmutable::now();

        // Sesi yang sudah lewat waktu tidak boleh ikut memenuhi kuota; ditutup lebih dulu.
        $this->tutupYangKedaluwarsa($demo, $sekarang);
        $this->pastikanKuotaTersisa($demo);

        return $this->transaksi->jalankan(function () use ($demo, $pengenalPengunjung, $sesiPengunjungId, $sekarang): SesiDemo {
            $sesi = SesiDemo::create([
                'DemoPemasaranId' => $demo->Id,
                'PengenalPengunjung' => $pengenalPengunjung,
                'SesiPengunjungId' => $sesiPengunjungId,
                'Status' => StatusSesiDemo::Berjalan,
                'MulaiPada' => $sekarang,
                'KedaluwarsaPada' => $sekarang->addMinutes($demo->MaksDurasiMenit),
            ]);

            $this->tulisEvent($sesi, JenisEventDemo::DemoDimulai, null, null, $sekarang);

            return $sesi;
        });
    }

    /** @param array<string, mixed>|null $rincian */
    public function catat(
        SesiDemo $sesi,
        JenisEventDemo $jenis,
        ?ModulDemo $modul = null,
        ?array $rincian = null,
    ): EventDemo {
        if ($jenis->ditulisLayanan()) {
            throw new AturanBisnisDilanggar(
                "Peristiwa {$jenis->value} hanya ditulis oleh batas sesinya sendiri.",
            );
        }

        $sekarang = CarbonImmutable::now();
        $sesi = $this->pastikanMasihBerjalan($sesi, $sekarang);
        $this->pastikanModulTampil($sesi, $modul);

        return $this->tulisEvent($sesi, $jenis, $modul, $rincian, $sekarang);
    }

    public function selesaikan(SesiDemo $sesi, string $alasan = 'Diselesaikan pengunjung.'): SesiDemo
    {
        $sekarang = CarbonImmutable::now();
        $sesi = $this->pastikanMasihBerjalan($sesi, $sekarang);

        return $this->transaksi->jalankan(function () use ($sesi, $alasan, $sekarang): SesiDemo {
            $this->tulisEvent($sesi, JenisEventDemo::DemoSelesai, null, null, $sekarang);

            $sesi->Status = StatusSesiDemo::Selesai;
            $sesi->SelesaiPada = $sekarang;
            $sesi->AlasanSelesai = $alasan;
            $sesi->save();

            return $sesi;
        });
    }

    /** Menutup sesi yang sudah lewat waktu; dipanggil saat sesi baru dimulai dan oleh job terjadwal. */
    public function tutupYangKedaluwarsa(?DemoPemasaran $demo = null, ?CarbonImmutable $pada = null): int
    {
        $sekarang = $pada ?? CarbonImmutable::now();

        return SesiDemo::query()
            ->where('Status', StatusSesiDemo::Berjalan->value)
            ->where('KedaluwarsaPada', '<=', $sekarang)
            ->when($demo !== null, fn ($kueri) => $kueri->where('DemoPemasaranId', $demo->Id))
            ->update([
                'Status' => StatusSesiDemo::Kedaluwarsa->value,
                'SelesaiPada' => $sekarang,
                'AlasanSelesai' => 'Melewati batas durasi sesi.',
            ]);
    }

    public function sesiBerjalan(DemoPemasaran $demo): int
    {
        return SesiDemo::query()
            ->where('DemoPemasaranId', $demo->Id)
            ->where('Status', StatusSesiDemo::Berjalan->value)
            ->count();
    }

    /** @param array<string, mixed>|null $rincian */
    private function tulisEvent(
        SesiDemo $sesi,
        JenisEventDemo $jenis,
        ?ModulDemo $modul,
        ?array $rincian,
        CarbonImmutable $pada,
    ): EventDemo {
        $event = EventDemo::create([
            'SesiDemoId' => $sesi->Id,
            'Jenis' => $jenis,
            'Modul' => $modul?->value,
            'Rincian' => $rincian,
            'TerjadiPada' => $pada,
        ]);

        // Tanpa baris kembarannya di EventPemasaran, tahap Demo di funnel growth akan selalu nol.
        $peristiwa = $jenis->peristiwaPemasaran();

        if ($peristiwa !== null) {
            $this->perekam->catat(
                $peristiwa,
                pengenalPengunjung: $sesi->PengenalPengunjung,
                sesiPengunjungId: $sesi->SesiPengunjungId,
                dataTambahan: ['SesiDemoId' => $sesi->Id, 'DemoPemasaranId' => $sesi->DemoPemasaranId],
            );
        }

        return $event;
    }

    private function pastikanMasihBerjalan(SesiDemo $sesi, CarbonImmutable $sekarang): SesiDemo
    {
        if ($sesi->Status->final()) {
            throw new AturanBisnisDilanggar("Sesi demo sudah {$sesi->Status->value}.");
        }

        // Batas durasi ditegakkan saat dipakai, bukan hanya saat job terjadwal kebetulan lewat.
        if ($sesi->KedaluwarsaPada->lessThanOrEqualTo($sekarang)) {
            $sesi->Status = StatusSesiDemo::Kedaluwarsa;
            $sesi->SelesaiPada = $sekarang;
            $sesi->AlasanSelesai = 'Melewati batas durasi sesi.';
            $sesi->save();

            throw new AturanBisnisDilanggar('Sesi demo sudah melewati batas durasinya.');
        }

        return $sesi;
    }

    private function pastikanKuotaTersisa(DemoPemasaran $demo): void
    {
        $berjalan = $this->sesiBerjalan($demo);

        if ($berjalan >= $demo->MaksSesiSerentak) {
            throw new AturanBisnisDilanggar(
                "Demo {$demo->Kode} sedang dipakai {$berjalan} pengunjung, sebanyak batasnya. Coba beberapa menit lagi.",
            );
        }
    }

    /** Modul yang tidak ditampilkan tidak boleh diam-diam terekam sebagai sudah dibuka. */
    private function pastikanModulTampil(SesiDemo $sesi, ?ModulDemo $modul): void
    {
        if ($modul === null) {
            return;
        }

        $tampil = $sesi->demo->ModulTampil ?? [];

        if (! in_array($modul->value, $tampil, true)) {
            throw new AturanBisnisDilanggar("Modul {$modul->value} tidak ditampilkan di demo ini.");
        }
    }
}
