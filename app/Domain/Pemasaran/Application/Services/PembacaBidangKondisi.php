<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Langganan\Application\Services\LayananLangganan;
use App\Domain\Pemasaran\Domain\KatalogKondisiOtomasi;
use App\Domain\Pemasaran\Domain\ValueObjects\KonteksOtomasi;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\TagProspek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Trial;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use Carbon\CarbonImmutable;

/** Membaca nilai satu bidang kondisi dari konteks eksekusi (MARKETING.md 17). */
final class PembacaBidangKondisi
{
    public function __construct(
        private readonly LayananLangganan $langganan,
        private readonly LayananKonsen $konsen,
    ) {}

    /** Nilai bidang, atau null bila memang tidak ada; null itulah yang dibaca operator Ada/TidakAda. */
    public function baca(string $bidang, KonteksOtomasi $konteks): mixed
    {
        $prospek = $konteks->prospek;
        $organisasiId = $konteks->organisasiId ?? $prospek?->OrganisasiId;

        return match ($bidang) {
            KatalogKondisiOtomasi::INDUSTRI => $prospek?->organisasiProspek->Industri,
            KatalogKondisiOtomasi::SUMBER => $prospek?->Sumber,
            KatalogKondisiOtomasi::KAMPANYE => $prospek?->kampanye->Kode,
            KatalogKondisiOtomasi::SKOR => $prospek === null ? null : (int) $prospek->Skor,
            KatalogKondisiOtomasi::TAHAP_PIPELINE => $prospek?->tahap->Kode,
            KatalogKondisiOtomasi::AKTIVITAS_TERAKHIR_HARI => $this->hariSejakAktivitas($konteks),
            KatalogKondisiOtomasi::TAG => $this->tag($konteks),
            KatalogKondisiOtomasi::CONSENT => $this->consent($konteks),
            KatalogKondisiOtomasi::STATUS_TRIAL => $this->statusTrial($organisasiId),
            KatalogKondisiOtomasi::PAKET => $this->paket($organisasiId),
            KatalogKondisiOtomasi::STATUS_LANGGANAN => $this->statusLangganan($organisasiId),
            KatalogKondisiOtomasi::JUMLAH_ASET => $this->jumlahAset($organisasiId),
            KatalogKondisiOtomasi::JUMLAH_LOKASI => $this->jumlahLokasi($organisasiId),
            default => null,
        };
    }

    private function hariSejakAktivitas(KonteksOtomasi $konteks): ?int
    {
        $terakhir = $konteks->prospek?->AktivitasTerakhirPada;

        if ($terakhir === null) {
            return null;
        }

        return (int) floor($terakhir->diffInDays(CarbonImmutable::now(), absolute: false));
    }

    /** @return list<string>|null */
    private function tag(KonteksOtomasi $konteks): ?array
    {
        $prospek = $konteks->prospek;

        if ($prospek === null) {
            return null;
        }

        return array_values($prospek->tag->map(
            fn (TagProspek $satu): string => (string) $satu->Nama,
        )->all());
    }

    private function consent(KonteksOtomasi $konteks): ?string
    {
        $email = (string) $konteks->prospek?->Email;

        if ($email === '') {
            return null;
        }

        return $this->konsen->bolehDikirimi($email) ? 'Ya' : 'Tidak';
    }

    private function statusTrial(?string $organisasiId): ?string
    {
        if ($organisasiId === null) {
            return null;
        }

        $trial = Trial::query()->where('OrganisasiId', $organisasiId)->first();

        return $trial?->Status->value;
    }

    private function paket(?string $organisasiId): ?string
    {
        if ($organisasiId === null) {
            return null;
        }

        return $this->langganan->untukOrganisasi($organisasiId)?->paketLangganan->Kode;
    }

    private function statusLangganan(?string $organisasiId): ?string
    {
        if ($organisasiId === null) {
            return null;
        }

        return $this->langganan->untukOrganisasi($organisasiId)?->Status;
    }

    /** Dihitung lintas tenant dengan filter organisasi eksplisit: otomasi berjalan tanpa konteks tenant. */
    private function jumlahAset(?string $organisasiId): ?int
    {
        if ($organisasiId === null) {
            return null;
        }

        return Aset::query()->withoutGlobalScopes()->where('OrganisasiId', $organisasiId)->count();
    }

    private function jumlahLokasi(?string $organisasiId): ?int
    {
        if ($organisasiId === null) {
            return null;
        }

        return Lokasi::query()->withoutGlobalScopes()->where('OrganisasiId', $organisasiId)->count();
    }
}
