<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Core\Host\PetaHost;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KodeReferral;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\ProgramReferral;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;

/** Kode dan URL referral satu pelanggan (MARKETING.md 20). */
final class PenerbitKodeReferral
{
    public const PANJANG_KODE = 10;

    private const PERCOBAAN_MAKS = 5;

    public function __construct(private readonly PetaHost $host) {}

    /** Satu pelanggan satu kode per program; memanggil ulang mengembalikan kode yang sama. */
    public function untuk(ProgramReferral $program, string $organisasiId): KodeReferral
    {
        $ada = KodeReferral::query()
            ->where('ProgramReferralId', $program->Id)
            ->where('OrganisasiId', $organisasiId)
            ->first();

        if ($ada !== null) {
            return $ada;
        }

        for ($percobaan = 0; $percobaan < self::PERCOBAAN_MAKS; $percobaan++) {
            try {
                return KodeReferral::create([
                    'ProgramReferralId' => $program->Id,
                    'OrganisasiId' => $organisasiId,
                    'Kode' => $this->kodeAcak(),
                    'Aktif' => true,
                    'DibuatPada' => CarbonImmutable::now(),
                ]);
            } catch (UniqueConstraintViolationException) {
                // Kode acak bertabrakan, atau pemiliknya sudah punya; keduanya dijawab dengan membaca ulang.
                $ada = KodeReferral::query()
                    ->where('ProgramReferralId', $program->Id)
                    ->where('OrganisasiId', $organisasiId)
                    ->first();

                if ($ada !== null) {
                    return $ada;
                }
            }
        }

        throw new AturanBisnisDilanggar('Gagal menerbitkan kode referral yang unik.');
    }

    /** Null bila situs publik tidak aktif; tanpa host publik tidak ada tempat kode itu ditautkan. */
    public function url(KodeReferral $kode): ?string
    {
        $publik = $this->host->urlKanonik('/');

        if ($publik === null) {
            return null;
        }

        return rtrim($publik, '/').'/r/'.$kode->Kode;
    }

    /** Huruf dan angka saja, tanpa yang mudah tertukar saat dibacakan lewat telepon. */
    private function kodeAcak(): string
    {
        $abjad = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $kode = '';

        for ($i = 0; $i < self::PANJANG_KODE; $i++) {
            $kode .= $abjad[random_int(0, mb_strlen($abjad) - 1)];
        }

        return $kode;
    }
}
