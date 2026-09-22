<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Pemasaran\Domain\Enums\OperatorKondisi;
use App\Domain\Pemasaran\Domain\KatalogKondisiOtomasi;
use App\Domain\Pemasaran\Domain\ValueObjects\KonteksOtomasi;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/** Menilai kondisi satu langkah otomasi; bidang yang tidak diketahui dinilai gagal (MARKETING.md 17). */
final class PengevaluasiKondisiOtomasi
{
    public function __construct(private readonly PembacaBidangKondisi $pembaca) {}

    /**
     * Seluruh kondisi dalam satu langkah harus terpenuhi; "atau" ditulis sebagai SalahSatuDari.
     *
     * @param  list<array<string, mixed>>  $kondisi
     */
    public function semuaTerpenuhi(array $kondisi, KonteksOtomasi $konteks): bool
    {
        foreach ($kondisi as $satu) {
            if (! $this->terpenuhi($satu, $konteks)) {
                return false;
            }
        }

        return true;
    }

    /** @param array<string, mixed> $kondisi */
    public function terpenuhi(array $kondisi, KonteksOtomasi $konteks): bool
    {
        $bidang = (string) ($kondisi['Bidang'] ?? '');
        $operator = OperatorKondisi::tryFrom((string) ($kondisi['Operator'] ?? ''));

        if (! KatalogKondisiOtomasi::dikenal($bidang) || $operator === null) {
            throw new AturanBisnisDilanggar("Kondisi {$bidang} tidak dikenal.");
        }

        $nilai = $this->pembaca->baca($bidang, $konteks);

        return $this->bandingkan($operator, $nilai, $kondisi['Nilai'] ?? null);
    }

    private function bandingkan(OperatorKondisi $operator, mixed $nilai, mixed $pembanding): bool
    {
        if ($operator === OperatorKondisi::Ada) {
            return $this->terisi($nilai);
        }

        if ($operator === OperatorKondisi::TidakAda) {
            return ! $this->terisi($nilai);
        }

        // Nilai yang tidak diketahui tidak pernah cocok: otomasi lebih baik diam daripada salah sasaran.
        if (! $this->terisi($nilai)) {
            return false;
        }

        if (is_array($nilai)) {
            return $this->bandingkanDaftar($operator, $nilai, $pembanding);
        }

        return match ($operator) {
            OperatorKondisi::SamaDengan => $this->teks($nilai) === $this->teks($pembanding),
            OperatorKondisi::TidakSamaDengan => $this->teks($nilai) !== $this->teks($pembanding),
            OperatorKondisi::LebihDari => (float) $nilai > (float) $pembanding,
            OperatorKondisi::KurangDari => (float) $nilai < (float) $pembanding,
            OperatorKondisi::SalahSatuDari => in_array($this->teks($nilai), $this->daftar($pembanding), true),
            // Ada dan TidakAda sudah dijawab di atas, jadi yang tersisa hanya Mengandung.
            default => str_contains($this->teks($nilai), $this->teks($pembanding)),
        };
    }

    /**
     * Bidang berisi daftar, seperti tag: pembandingnya diperiksa terhadap seluruh isinya.
     *
     * @param  array<array-key, mixed>  $nilai
     */
    private function bandingkanDaftar(OperatorKondisi $operator, array $nilai, mixed $pembanding): bool
    {
        $isi = array_map($this->teks(...), array_values($nilai));

        return match ($operator) {
            OperatorKondisi::SamaDengan, OperatorKondisi::Mengandung => in_array(
                $this->teks($pembanding), $isi, true,
            ),
            OperatorKondisi::TidakSamaDengan => ! in_array($this->teks($pembanding), $isi, true),
            OperatorKondisi::SalahSatuDari => array_intersect($this->daftar($pembanding), $isi) !== [],
            default => false,
        };
    }

    private function terisi(mixed $nilai): bool
    {
        return $nilai !== null && $nilai !== '' && $nilai !== [];
    }

    private function teks(mixed $nilai): string
    {
        return mb_strtolower(trim((string) (is_scalar($nilai) ? $nilai : '')));
    }

    /** @return list<string> */
    private function daftar(mixed $pembanding): array
    {
        $isi = is_array($pembanding) ? $pembanding : explode(',', (string) $pembanding);

        return array_values(array_filter(array_map($this->teks(...), $isi), fn (string $s): bool => $s !== ''));
    }
}
