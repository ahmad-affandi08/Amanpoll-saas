<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Jobs;

use App\Domain\Pemasaran\Application\Services\PenerbitKontenSosial;
use App\Domain\Pemasaran\Domain\Enums\StatusKontenSosial;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\DistribusiKontenSosial;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\JadwalKontenSosial;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/** Idempoten lewat klaim atomik di penerbitnya: hanya satu proses yang berhasil merebut distribusinya. */
final class TerbitkanKontenSosial implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly string $jadwalId) {}

    public function handle(PenerbitKontenSosial $penerbit): void
    {
        $jadwal = JadwalKontenSosial::query()->find($this->jadwalId);

        if ($jadwal === null) {
            return;
        }

        $penerbit->terbitkan($jadwal);
    }

    public function failed(?Throwable $galat): void
    {
        $jadwal = JadwalKontenSosial::query()->find($this->jadwalId);
        $distribusi = $jadwal?->distribusi;

        if (! $distribusi instanceof DistribusiKontenSosial) {
            return;
        }

        // Yang tersangkut di Diproses tidak akan pernah lepas sendiri; ditandai gagal agar dapat diulang.
        if ($distribusi->Status !== StatusKontenSosial::Diproses) {
            return;
        }

        $distribusi->Status = StatusKontenSosial::Gagal;
        $distribusi->Galat = mb_substr((string) $galat?->getMessage(), 0, 500);
        $distribusi->DiprosesPada = CarbonImmutable::now();
        $distribusi->save();
    }

    public function uniqueId(): string
    {
        return $this->jadwalId;
    }
}
