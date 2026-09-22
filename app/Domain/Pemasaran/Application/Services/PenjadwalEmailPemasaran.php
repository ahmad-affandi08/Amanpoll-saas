<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Pemasaran\Domain\Enums\StatusPengirimanEmail;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\LangkahSequenceEmail;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\PendaftaranSequence;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\PengirimanEmailPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Prospek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\TemplateEmailPemasaran;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;

/** Menjadwalkan satu kiriman sekali saja; indeks unik yang jadi wasitnya, bukan pemeriksaan sebelum menulis (MARKETING.md 15). */
final class PenjadwalEmailPemasaran
{
    public function __construct(private readonly PerenderTemplateEmail $perender) {}

    public function jadwalkanLangkah(
        PendaftaranSequence $pendaftaran,
        LangkahSequenceEmail $langkah,
        Prospek $prospek,
    ): ?PengirimanEmailPemasaran {
        $template = $langkah->template;

        if ($template === null || ! $template->Aktif) {
            return null;
        }

        return $this->jadwalkan(
            $prospek,
            $template,
            $pendaftaran->DimulaiPada->addDays($langkah->HariKe),
            "sequence:{$pendaftaran->Id}:langkah:{$langkah->Id}",
            $pendaftaran,
            $langkah,
        );
    }

    public function jadwalkan(
        Prospek $prospek,
        TemplateEmailPemasaran $template,
        CarbonImmutable $jadwalPada,
        string $kunciIdempotensi,
        ?PendaftaranSequence $pendaftaran = null,
        ?LangkahSequenceEmail $langkah = null,
    ): ?PengirimanEmailPemasaran {
        $email = (string) $prospek->Email;

        if ($email === '') {
            return null;
        }

        $dirender = $this->perender->render($template, $prospek);

        try {
            return PengirimanEmailPemasaran::create([
                'ProspekId' => $prospek->Id,
                'Email' => mb_strtolower($email),
                'TemplateEmailPemasaranId' => $template->Id,
                'PendaftaranSequenceId' => $pendaftaran?->Id,
                'LangkahSequenceEmailId' => $langkah?->Id,
                'KunciIdempotensi' => $kunciIdempotensi,
                'Status' => StatusPengirimanEmail::Terjadwal,
                'Subjek' => $dirender['Subjek'],
                'JadwalPada' => $jadwalPada,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Sudah dijadwalkan sebelumnya; itu justru hasil yang diinginkan.
            return PengirimanEmailPemasaran::query()
                ->where('KunciIdempotensi', $kunciIdempotensi)
                ->first();
        }
    }
}
