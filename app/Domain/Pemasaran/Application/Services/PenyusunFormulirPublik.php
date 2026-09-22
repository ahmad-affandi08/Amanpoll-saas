<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Pemasaran\Infrastructure\Persistence\Models\FieldFormulirPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\FormulirPemasaran;

/** Bentuk formulir sebagaimana dilihat pengunjung (MARKETING.md 10). */
final class PenyusunFormulirPublik
{
    public function __construct(private readonly PemeriksaCaptcha $captcha) {}

    /** @return array<string, mixed> */
    public function susun(FormulirPemasaran $formulir): array
    {
        $formulir->loadMissing('field');

        return [
            'Kode' => $formulir->Kode,
            'Nama' => $formulir->Nama,
            'WajibPersetujuan' => $formulir->WajibPersetujuan,
            'CaptchaAktif' => $formulir->CaptchaAktif,
            // Hanya dikirim saat CAPTCHA memang menyala.
            'Captcha' => $formulir->CaptchaAktif ? [
                'KunciSitus' => config('amanpoll.pemasaran.captcha.kunci_situs'),
                'Skrip' => config('amanpoll.pemasaran.captcha.skrip'),
                'NamaField' => $this->captcha->namaField(),
            ] : null,
            'Field' => $formulir->field
                ->reject(fn (FieldFormulirPemasaran $field): bool => $field->Jenis->terisiOtomatis())
                ->map(fn (FieldFormulirPemasaran $field): array => [
                    'Kode' => $field->Kode,
                    'Label' => $field->Label,
                    'Jenis' => $field->Jenis->value,
                    'Wajib' => $field->Wajib,
                    'Pilihan' => $field->Pilihan ?? [],
                    'Placeholder' => $field->Placeholder,
                    'Bantuan' => $field->Bantuan,
                ])
                ->values()
                ->all(),
        ];
    }
}
