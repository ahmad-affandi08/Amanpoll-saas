<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Core\Audit\LayananAudit;
use App\Domain\Pemasaran\Domain\Contracts\PenyediaWhatsApp;
use App\Domain\Pemasaran\Domain\Enums\StatusPersetujuanTemplateWa;
use App\Domain\Pemasaran\Domain\ValueObjects\PersetujuanTemplateWa;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\TemplateWhatsAppPemasaran;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;

/** Status persetujuan hanya boleh berubah lewat penyedia atau jalur manual yang tercatat (MARKETING.md 16). */
final class LayananTemplateWhatsApp
{
    public function __construct(
        private readonly PenyediaWhatsApp $penyedia,
        private readonly LayananAudit $audit,
    ) {}

    public function ajukan(TemplateWhatsAppPemasaran $template): StatusPersetujuanTemplateWa
    {
        $this->pastikanTransisiSah($template->StatusPersetujuan, StatusPersetujuanTemplateWa::Diajukan);

        $keputusan = $this->penyedia->ajukanTemplate(
            $template->Kode,
            $template->Bahasa,
            $template->Kategori,
            $template->IsiTeks,
        );

        return $this->terapkan($template, $keputusan, 'Diajukan');
    }

    public function periksa(TemplateWhatsAppPemasaran $template): StatusPersetujuanTemplateWa
    {
        $keputusan = $this->penyedia->periksaTemplate($template->Kode);

        if ($keputusan->status === $template->StatusPersetujuan) {
            $template->DiperiksaPada = CarbonImmutable::now();
            $template->save();

            return $template->StatusPersetujuan;
        }

        if (! $template->StatusPersetujuan->bolehPindahKe($keputusan->status)) {
            return $template->StatusPersetujuan;
        }

        return $this->terapkan($template, $keputusan, 'Diperiksa');
    }

    /** Jalur manual untuk penyedia tanpa API: operator hanya menyalin keputusan penyedia, dan itu masuk audit. */
    public function catatKeputusanManual(
        TemplateWhatsAppPemasaran $template,
        StatusPersetujuanTemplateWa $status,
        ?string $idTemplatePenyedia = null,
        ?string $alasan = null,
    ): StatusPersetujuanTemplateWa {
        $this->pastikanTransisiSah($template->StatusPersetujuan, $status);

        return $this->terapkan(
            $template,
            new PersetujuanTemplateWa($status, $idTemplatePenyedia, $alasan),
            'KeputusanManual',
        );
    }

    private function terapkan(
        TemplateWhatsAppPemasaran $template,
        PersetujuanTemplateWa $keputusan,
        string $aksi,
    ): StatusPersetujuanTemplateWa {
        $sebelum = $template->StatusPersetujuan->value;

        $template->StatusPersetujuan = $keputusan->status;
        $template->IdTemplatePenyedia = $keputusan->idTemplatePenyedia ?? $template->IdTemplatePenyedia;
        $template->AlasanPenolakan = $keputusan->alasan;
        $template->DiperiksaPada = CarbonImmutable::now();
        $template->save();

        $this->audit->catat(
            "TemplateWhatsApp.{$aksi}",
            'TemplateWhatsAppPemasaran',
            $template->Id,
            dataSebelum: ['StatusPersetujuan' => $sebelum],
            dataSesudah: ['StatusPersetujuan' => $keputusan->status->value, 'Alasan' => $keputusan->alasan],
        );

        return $keputusan->status;
    }

    private function pastikanTransisiSah(
        StatusPersetujuanTemplateWa $asal,
        StatusPersetujuanTemplateWa $tujuan,
    ): void {
        if ($asal->bolehPindahKe($tujuan)) {
            return;
        }

        $sah = implode(', ', array_map(
            fn (StatusPersetujuanTemplateWa $satu): string => $satu->value,
            $asal->tujuanSah(),
        ));

        throw new AturanBisnisDilanggar(
            "Template berstatus {$asal->value} hanya dapat berpindah ke {$sah}.",
        );
    }
}
