<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Pemasaran\Domain\Enums\KanalPesan;
use App\Domain\Pemasaran\Domain\Enums\StatusPengirimanWhatsApp;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\PengirimanWhatsAppPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Prospek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\TemplateWhatsAppPemasaran;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;

/** Menjadwalkan satu kiriman sekali saja; indeks unik yang jadi wasitnya, bukan pemeriksaan sebelum menulis (MARKETING.md 16). */
final class PenjadwalWhatsAppPemasaran
{
    public function __construct(private readonly PerenderTemplateWhatsApp $perender) {}

    public function jadwalkan(
        Prospek $prospek,
        TemplateWhatsAppPemasaran $template,
        CarbonImmutable $jadwalPada,
        string $kunciIdempotensi,
    ): ?PengirimanWhatsAppPemasaran {
        $nomor = KanalPesan::WhatsApp->normalkan((string) ($prospek->WhatsApp ?? $prospek->Telepon ?? ''));

        if ($nomor === '') {
            return null;
        }

        // Template yang belum disetujui penyedia ditolak sejak dijadwalkan, bukan baru saat berangkat.
        if (! $template->siapKirim()) {
            throw new AturanBisnisDilanggar(
                "Template WhatsApp {$template->Kode} belum disetujui penyedia atau sedang nonaktif.",
            );
        }

        try {
            return PengirimanWhatsAppPemasaran::create([
                'ProspekId' => $prospek->Id,
                'Nomor' => $nomor,
                'TemplateWhatsAppPemasaranId' => $template->Id,
                'KunciIdempotensi' => $kunciIdempotensi,
                'Status' => StatusPengirimanWhatsApp::Terjadwal,
                'IsiTeks' => $this->perender->render($template, $prospek),
                'JadwalPada' => $jadwalPada,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Sudah dijadwalkan sebelumnya; itu justru hasil yang diinginkan.
            return PengirimanWhatsAppPemasaran::query()
                ->where('KunciIdempotensi', $kunciIdempotensi)
                ->first();
        }
    }
}
