<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Jobs;

use App\Core\Organisasi\ScopeOrganisasi;
use App\Domain\Notifikasi\Infrastructure\Persistence\Models\Notifikasi;
use App\Domain\Notifikasi\Notifications\NotifikasiUmum;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Berjalan di luar konteks HTTP (worker antrian tidak punya
 * KonteksOrganisasi aktif), jadi ScopeOrganisasi sengaja dilewati --
 * baris Notifikasi dicari lewat Id ULID-nya sendiri yang sudah unik
 * lintas organisasi, pola yang sama seperti BersihkanCatatanAkses.
 */
final class KirimNotifikasi implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(private readonly string $notifikasiId) {}

    public function handle(): void
    {
        $notifikasi = Notifikasi::withoutGlobalScope(ScopeOrganisasi::class)->find($this->notifikasiId);
        if (! $notifikasi || $notifikasi->Status !== Notifikasi::STATUS_ANTRI) {
            return;
        }

        $notifikasi->Percobaan++;

        try {
            if ($notifikasi->Kanal === Notifikasi::KANAL_EMAIL) {
                $this->kirimEmail($notifikasi);
            }

            $notifikasi->Status = Notifikasi::STATUS_TERKIRIM;
            $notifikasi->DikirimPada = now()->toImmutable();
            $notifikasi->save();
        } catch (Throwable $e) {
            $notifikasi->KesalahanTerakhir = $e->getMessage();
            $notifikasi->save();

            throw $e;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $notifikasi = Notifikasi::withoutGlobalScope(ScopeOrganisasi::class)->find($this->notifikasiId);
        if (! $notifikasi) {
            return;
        }

        $notifikasi->Status = Notifikasi::STATUS_GAGAL;
        $notifikasi->KesalahanTerakhir = $exception?->getMessage();
        $notifikasi->save();
    }

    private function kirimEmail(Notifikasi $notifikasi): void
    {
        $pengguna = Pengguna::query()->find($notifikasi->PenggunaId);
        if (! $pengguna) {
            return;
        }

        $pengguna->notify(new NotifikasiUmum($notifikasi->Judul, $notifikasi->Isi));
    }
}
