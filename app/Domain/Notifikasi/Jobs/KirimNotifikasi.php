<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Jobs;

use App\Core\Organisasi\ScopeOrganisasi;
use App\Domain\Notifikasi\Application\Services\TujuanWhatsAppNotifikasi;
use App\Domain\Notifikasi\Domain\Contracts\DapatMengirimNotifikasiWhatsApp;
use App\Domain\Notifikasi\Domain\Enums\KanalNotifikasi;
use App\Domain\Notifikasi\Domain\Enums\StatusNotifikasi;
use App\Domain\Notifikasi\Infrastructure\Persistence\Models\Notifikasi;
use App\Domain\Notifikasi\Notifications\NotifikasiUmum;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Domain\ValueObjects\NomorWhatsApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/** Berjalan di luar konteks HTTP (worker antrian tidak punya KonteksOrganisasi aktif). */
final class KirimNotifikasi implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * Kanal luar yang sedang bermasalah butuh jeda, bukan tiga hantaman beruntun.
     *
     * @var list<int>
     */
    public array $backoff = [30, 120];

    public function __construct(private readonly string $notifikasiId) {}

    public function handle(): void
    {
        $notifikasi = Notifikasi::withoutGlobalScope(ScopeOrganisasi::class)->find($this->notifikasiId);
        if (! $notifikasi || $notifikasi->Status !== StatusNotifikasi::Antri->value) {
            return;
        }

        $notifikasi->Percobaan++;

        try {
            if ($notifikasi->Kanal === KanalNotifikasi::Email->value) {
                $this->kirimEmail($notifikasi);
            }

            if ($notifikasi->Kanal === KanalNotifikasi::WhatsApp->value) {
                $this->kirimWhatsApp($notifikasi);
            }

            $notifikasi->Status = StatusNotifikasi::Terkirim->value;
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

        $notifikasi->Status = StatusNotifikasi::Gagal->value;
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

    /**
     * Pesan operasional ke staf: tidak melewati consent pemasaran, hanya preferensi yang
     * sudah diperiksa saat barisnya dibuat. Galatnya disimpan di KesalahanTerakhir, jadi
     * nomor penerima disamarkan dan galat tak dikenal tidak diteruskan apa adanya.
     */
    private function kirimWhatsApp(Notifikasi $notifikasi): void
    {
        $nomor = app(TujuanWhatsAppNotifikasi::class)->nomorUntuk($notifikasi->PenggunaId);

        if ($nomor === null) {
            throw new AturanBisnisDilanggar('Pengguna tidak lagi memiliki nomor telepon yang dapat dipakai WhatsApp.');
        }

        try {
            app(DapatMengirimNotifikasiWhatsApp::class)->kirimNotifikasi($nomor, (string) $notifikasi->Judul, $notifikasi->Isi);
        } catch (AturanBisnisDilanggar $galat) {
            throw new AturanBisnisDilanggar(
                str_replace($nomor, NomorWhatsApp::samarkan($nomor), $galat->getMessage()),
                previous: $galat,
            );
        } catch (Throwable $galat) {
            throw new AturanBisnisDilanggar('Pengiriman WhatsApp gagal karena galat tak terduga ('.class_basename($galat).').', previous: $galat);
        }
    }
}
