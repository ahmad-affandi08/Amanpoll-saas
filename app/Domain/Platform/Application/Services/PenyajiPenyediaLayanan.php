<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Services;

use App\Domain\Platform\Domain\Contracts\DapatDiujiKoneksi;
use App\Domain\Platform\Domain\Contracts\DeskripsiPenyediaLayanan;
use App\Domain\Platform\Domain\ValueObjects\IsianKredensial;

/**
 * Bentuk satu penyedia untuk halaman pengaturan, di konsol platform maupun organisasi (PRD 8.23).
 *
 * Nilai isian rahasia tidak pernah ikut: halaman hanya menerima penanda "tersimpan" dan
 * empat karakter terakhir rahasia yang cukup panjang untuk tetap tak tertebak.
 */
final class PenyajiPenyediaLayanan
{
    private const PANJANG_MINIMUM_AKHIRAN = 12;

    public function __construct(private readonly PenyusunKredensialPenyedia $penyusun) {}

    /**
     * @param  array<string, string>  $nilai  kredensial tersimpan yang sudah didekripsi
     * @return array{Kode: string, Nama: string, Keterangan: string, Resmi: bool, MendukungModeUji: bool, DapatDiuji: bool, Isian: list<array<string, mixed>>}
     */
    public function ringkas(DeskripsiPenyediaLayanan $penyedia, array $nilai, bool $organisasi = false): array
    {
        return [
            'Kode' => $penyedia->kode(),
            'Nama' => $penyedia->nama(),
            'Keterangan' => $penyedia->keterangan(),
            'Resmi' => $penyedia->resmi(),
            'MendukungModeUji' => $penyedia->mendukungModeUji(),
            'DapatDiuji' => $penyedia instanceof DapatDiujiKoneksi,
            'Isian' => array_map(
                fn (IsianKredensial $isian): array => [
                    ...$isian->keArray(),
                    'Wajib' => $isian->wajibBagi($organisasi),
                    'Nilai' => $isian->rahasia ? null : ($nilai[$isian->kunci] ?? $isian->bawaan ?? ''),
                    'Tersimpan' => ($nilai[$isian->kunci] ?? '') !== '',
                    'Akhiran' => $isian->rahasia && ($nilai[$isian->kunci] ?? '') !== ''
                        ? $this->akhiran($nilai[$isian->kunci])
                        : null,
                ],
                $this->penyusun->isianBagi($penyedia, $organisasi),
            ),
        ];
    }

    private function akhiran(string $rahasia): ?string
    {
        return mb_strlen($rahasia) >= self::PANJANG_MINIMUM_AKHIRAN ? mb_substr($rahasia, -4) : null;
    }
}
