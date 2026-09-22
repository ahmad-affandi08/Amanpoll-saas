<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Core\Host\PetaHost;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\DistribusiKontenSosial;

/** Menempeli tautan distribusi dengan UTM, sehingga trafiknya tertaut ke kampanyenya (MARKETING.md 14, 18). */
final class PenyusunTautanDistribusi
{
    public function __construct(private readonly PetaHost $host) {}

    public function untuk(DistribusiKontenSosial $distribusi): ?string
    {
        $dasar = $this->tautanDasar($distribusi);

        if ($dasar === null) {
            return null;
        }

        $utm = $this->parameter($distribusi);

        if ($utm === []) {
            return $dasar;
        }

        // Parameter yang sudah ada di tautannya dipertahankan; UTM hanya ditambahkan.
        $pemisah = str_contains($dasar, '?') ? '&' : '?';

        return $dasar.$pemisah.http_build_query($utm);
    }

    /**
     * Lima parameter UTM distribusi ini; utm_campaign selalu dari kode kampanye induknya.
     *
     * @return array<string, string>
     */
    public function parameter(DistribusiKontenSosial $distribusi): array
    {
        $konten = $distribusi->konten;

        $utm = [
            'utm_source' => $distribusi->UtmSource ?? $distribusi->Channel->utmSource(),
            'utm_medium' => $distribusi->UtmMedium ?? 'social',
            'utm_campaign' => $konten?->kampanye?->Kode,
            'utm_term' => $distribusi->UtmTerm,
            'utm_content' => $distribusi->UtmContent ?? $konten?->Kode,
        ];

        return array_filter(
            $utm,
            static fn (?string $nilai): bool => $nilai !== null && $nilai !== '',
        );
    }

    /** Tautan distribusi sendiri menang; bila kosong, halaman yang dipromosikan kontennya yang dipakai. */
    private function tautanDasar(DistribusiKontenSosial $distribusi): ?string
    {
        if ($distribusi->TautanTujuan !== null && $distribusi->TautanTujuan !== '') {
            return $distribusi->TautanTujuan;
        }

        $slug = $distribusi->konten?->halaman?->Slug;
        $publik = $this->host->urlKanonik('/');

        if ($slug === null || $publik === null) {
            return null;
        }

        return rtrim($publik, '/').'/'.ltrim($slug, '/');
    }
}
