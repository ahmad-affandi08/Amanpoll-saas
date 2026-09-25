<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Services;

use App\Domain\Platform\Domain\Contracts\DeskripsiPenyediaLayanan;
use App\Domain\Platform\Domain\ValueObjects\IsianKredensial;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/**
 * Aturan menyusun kredensial dari formulir, dipakai konsol platform dan pengaturan organisasi (PRD 8.23).
 *
 * Isian rahasia yang dikirim kosong mempertahankan nilai lama, sebab peramban tidak pernah
 * menerima nilainya. Untuk organisasi, isian `hanyaPlatform` tidak disimpan sama sekali.
 */
final class PenyusunKredensialPenyedia
{
    /**
     * @param  array<string, string>  $lama
     * @param  array<string, mixed>  $masukan
     * @return array<string, string>
     */
    public function gabungkan(DeskripsiPenyediaLayanan $deskripsi, array $lama, array $masukan, bool $organisasi = false): array
    {
        $hasil = [];

        foreach ($this->isianBagi($deskripsi, $organisasi) as $isian) {
            $dikirim = array_key_exists($isian->kunci, $masukan);
            $baru = trim((string) (is_scalar($masukan[$isian->kunci] ?? null) ? $masukan[$isian->kunci] : ''));

            $nilai = match (true) {
                $isian->rahasia && $baru === '' => $lama[$isian->kunci] ?? '',
                $dikirim => $baru,
                default => $lama[$isian->kunci] ?? (string) $isian->bawaan,
            };

            if ($nilai !== '' && $isian->pilihan !== [] && ! in_array($nilai, $isian->pilihan, true)) {
                throw new AturanBisnisDilanggar("Pilihan {$isian->label} tidak dikenal.");
            }

            if ($nilai !== '') {
                $hasil[$isian->kunci] = $nilai;
            }
        }

        return $hasil;
    }

    /** @param  array<string, string>  $nilai */
    public function pastikanLengkap(DeskripsiPenyediaLayanan $deskripsi, array $nilai, bool $organisasi = false): void
    {
        $kosong = array_map(
            fn (IsianKredensial $isian): string => $isian->label,
            array_filter(
                $this->isianBagi($deskripsi, $organisasi),
                fn (IsianKredensial $isian): bool => $isian->wajibBagi($organisasi) && ($nilai[$isian->kunci] ?? '') === '',
            ),
        );

        if ($kosong !== []) {
            throw new AturanBisnisDilanggar(
                "{$deskripsi->nama()} belum bisa diaktifkan. Lengkapi dulu: ".implode(', ', $kosong).'.',
            );
        }
    }

    /**
     * HMAC berkunci APP_KEY: sidik tidak bisa dipakai menebak nilai kredensial yang pendek.
     *
     * @param  array<string, string>  $nilai
     */
    public function sidik(array $nilai): string
    {
        ksort($nilai);

        return hash_hmac(
            'sha256',
            (string) json_encode($nilai, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            (string) config('app.key'),
        );
    }

    /** @return list<IsianKredensial> */
    public function isianBagi(DeskripsiPenyediaLayanan $deskripsi, bool $organisasi): array
    {
        if (! $organisasi) {
            return $deskripsi->isian();
        }

        return array_values(array_filter(
            $deskripsi->isian(),
            fn (IsianKredensial $isian): bool => $isian->ditanyakanKepadaOrganisasi(),
        ));
    }
}
