<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Core\Audit\LayananAudit;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\LangkahSequenceEmail;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\TemplateEmailPemasaran;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/** Menyimpan template; variabel salah ketik ditolak di sini karena saat kirim ia hanya lenyap tanpa suara (MARKETING.md 15). */
final class LayananTemplateEmail
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly PerenderTemplateEmail $perender,
        private readonly LayananAudit $audit,
    ) {}

    /** @param array{Kode: string, Nama: string, Jenis: string, Subjek: string, IsiHtml: string, IsiTeks?: string|null, Aktif: bool} $data */
    public function simpan(?TemplateEmailPemasaran $template, array $data): TemplateEmailPemasaran
    {
        $asing = $this->perender->variabelAsing(
            $data['Subjek'],
            $data['IsiHtml'],
            (string) ($data['IsiTeks'] ?? ''),
        );

        if ($asing !== []) {
            throw new AturanBisnisDilanggar(
                'Variabel tidak dikenal: '.implode(', ', $asing).'. Yang tersedia: '
                .implode(', ', $this->perender->variabelDikenal()).'.',
            );
        }

        return $this->transaksi->jalankan(function () use ($template, $data): TemplateEmailPemasaran {
            $sebelum = $template?->only(['Kode', 'Nama', 'Subjek', 'Aktif']);

            if ($template === null) {
                $template = TemplateEmailPemasaran::create($data);
            } else {
                $template->fill($data);
                $template->save();
            }

            $this->audit->catat(
                $sebelum === null ? 'TemplateEmailPemasaran.Dibuat' : 'TemplateEmailPemasaran.Diubah',
                'TemplateEmailPemasaran',
                $template->Id,
                dataSebelum: $sebelum,
                dataSesudah: $template->only(['Kode', 'Nama', 'Subjek', 'Aktif']),
            );

            return $template;
        });
    }

    /** Template yang masih dirujuk langkah sequence tidak dihapus, cukup dinonaktifkan. */
    public function hapus(TemplateEmailPemasaran $template): void
    {
        $dipakai = LangkahSequenceEmail::query()
            ->where('TemplateEmailPemasaranId', $template->Id)
            ->count();

        if ($dipakai > 0) {
            throw new AturanBisnisDilanggar(
                "Template ini masih dipakai {$dipakai} langkah sequence. Nonaktifkan saja.",
            );
        }

        $this->audit->catat(
            'TemplateEmailPemasaran.Dihapus',
            'TemplateEmailPemasaran',
            $template->Id,
            dataSebelum: $template->only(['Kode', 'Nama', 'Subjek']),
        );

        $template->delete();
    }
}
