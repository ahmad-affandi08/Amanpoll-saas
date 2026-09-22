<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Pemasaran\Domain\Contracts\PenyediaSosial;
use App\Domain\Pemasaran\Domain\Enums\StatusJadwalSosial;
use App\Domain\Pemasaran\Domain\Enums\StatusKontenSosial;
use App\Domain\Pemasaran\Domain\ValueObjects\PosSosial;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\DistribusiKontenSosial;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\JadwalKontenSosial;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Throwable;

/** Menerbitkan satu distribusi tepat sekali, berapa kali pun jobnya diulang (MARKETING.md 18, 29). */
final class PenerbitKontenSosial
{
    public function __construct(
        private readonly PenyediaSosial $penyedia,
        private readonly PenyusunTautanDistribusi $penyusun,
    ) {}

    public function terbitkan(JadwalKontenSosial $jadwal): StatusKontenSosial
    {
        $distribusi = $jadwal->distribusi;

        if ($distribusi === null) {
            $this->tutupJadwal($jadwal, 'Distribusinya sudah tidak ada.');

            return StatusKontenSosial::Gagal;
        }

        if (! $this->klaim($distribusi)) {
            // Sudah diklaim proses lain, atau sudah terbit; keduanya berarti tidak ada yang perlu dikirim.
            return $distribusi->refresh()->Status;
        }

        $this->tutupJadwal($jadwal, null);
        $distribusi->refresh();

        try {
            $hasil = $this->penyedia->terbitkan(new PosSosial(
                channel: $distribusi->Channel,
                caption: $distribusi->Caption,
                mediaUrl: $distribusi->MediaUrl,
                tautan: $this->penyusun->untuk($distribusi),
                kunciIdempotensi: $this->kunci($distribusi),
            ));
        } catch (Throwable $galat) {
            $distribusi->Status = StatusKontenSosial::Gagal;
            $distribusi->Galat = mb_substr($galat->getMessage(), 0, 500);
            $distribusi->save();

            return StatusKontenSosial::Gagal;
        }

        $distribusi->Status = StatusKontenSosial::Terbit;
        $distribusi->IdPostPenyedia = $hasil->idPost;
        $distribusi->UrlTerbit = $hasil->url;
        $distribusi->Galat = null;
        $distribusi->TerbitPada = CarbonImmutable::now();
        $distribusi->save();

        return StatusKontenSosial::Terbit;
    }

    /** Kunci yang dibawa ke penyedia; tetap sama untuk satu distribusi, sehingga penyedia pun dapat menolak duplikat. */
    public function kunci(DistribusiKontenSosial $distribusi): string
    {
        return "distribusi:{$distribusi->Id}";
    }

    /** Klaim atomik: satu UPDATE yang hanya berhasil bila statusnya masih Terjadwal. */
    private function klaim(DistribusiKontenSosial $distribusi): bool
    {
        // Memeriksa lalu menulis akan lolos ketika dua pekerja berjalan bersamaan.
        $terklaim = DB::table('DistribusiKontenSosial')
            ->where('Id', $distribusi->Id)
            ->where('Status', StatusKontenSosial::Terjadwal->value)
            ->update([
                'Status' => StatusKontenSosial::Diproses->value,
                'Percobaan' => DB::raw('Percobaan + 1'),
                'DiprosesPada' => CarbonImmutable::now(),
            ]);

        return $terklaim === 1;
    }

    private function tutupJadwal(JadwalKontenSosial $jadwal, ?string $catatan): void
    {
        $jadwal->Status = StatusJadwalSosial::Dijalankan;
        $jadwal->DijalankanPada = CarbonImmutable::now();

        if ($catatan !== null) {
            $jadwal->Catatan = $catatan;
        }

        $jadwal->save();
    }
}
