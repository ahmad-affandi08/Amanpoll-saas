<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Application\Services;

use App\Domain\Langganan\Domain\Enums\StatusLangganan;
use App\Domain\Langganan\Infrastructure\Persistence\Models\Langganan;
use Carbon\CarbonImmutable;

/**
 * Pembacaan status langganan satu organisasi (22.04).
 *
 * Status efektif dihitung dari tanggal, bukan hanya dibaca dari kolom Status.
 * Kolomnya bisa tertinggal — perintah penyegar berjalan sekali sehari, dan
 * sebuah langganan bisa lewat tengah malam di antara dua jalannya. Menghitung
 * ulang di sini membuat pemblokiran tidak pernah terlambat satu hari, dan
 * membuat hasilnya sama baik diakses lewat web maupun lewat API.
 */
final class LayananLangganan
{
    public function __construct(private readonly LayananKebijakanTenggang $kebijakan) {}

    /**
     * Langganan yang sedang berlaku untuk sebuah organisasi. Bila ada lebih
     * dari satu baris (mis. sisa riwayat penggantian paket), yang dipakai
     * adalah yang terakhir dimulai.
     */
    public function untukOrganisasi(string $organisasiId): ?Langganan
    {
        return Langganan::query()
            ->withoutGlobalScopes()
            ->with('paketLangganan')
            ->where('OrganisasiId', $organisasiId)
            ->orderByDesc('MulaiPada')
            ->orderByDesc('DibuatPada')
            ->first();
    }

    /**
     * Status efektif hari ini. Urutan pemeriksaan penting: pembatalan mengunci
     * apa pun tanggalnya, lalu uji coba yang masih berjalan, baru perbandingan
     * dengan tanggal berakhir.
     */
    public function statusEfektif(Langganan $langganan, ?CarbonImmutable $pada = null): StatusLangganan
    {
        $pada ??= CarbonImmutable::now();
        $hariIni = $pada->startOfDay();

        $tersimpan = StatusLangganan::tryFrom((string) $langganan->Status);
        if ($tersimpan === StatusLangganan::Dibatalkan) {
            return StatusLangganan::Dibatalkan;
        }

        $ujiCobaSampai = $langganan->UjiCobaSampai;
        if ($ujiCobaSampai !== null && $hariIni->lessThanOrEqualTo(CarbonImmutable::parse($ujiCobaSampai)->startOfDay())) {
            return StatusLangganan::UjiCoba;
        }

        $berakhirPada = $langganan->BerakhirPada;
        if ($berakhirPada === null) {
            // Langganan tanpa tanggal akhir adalah langganan berjalan; ini
            // dipakai paket internal dan pelanggan dengan kontrak terpisah.
            return StatusLangganan::Aktif;
        }

        $akhir = CarbonImmutable::parse($berakhirPada)->startOfDay();
        if ($hariIni->lessThanOrEqualTo($akhir)) {
            return StatusLangganan::Aktif;
        }

        $akhirTenggang = $akhir->addDays($this->kebijakan->hariTenggang());

        return $hariIni->lessThanOrEqualTo($akhirTenggang)
            ? StatusLangganan::Tenggang
            : StatusLangganan::Kedaluwarsa;
    }

    /**
     * Uji coba yang sudah lewat tidak boleh ikut dilaporkan sebagai uji coba
     * berjalan, sehingga tanggalnya hanya dikembalikan selama masih relevan.
     */
    public function ujiCobaMasihBerjalan(Langganan $langganan, ?CarbonImmutable $pada = null): bool
    {
        $ujiCobaSampai = $langganan->UjiCobaSampai;
        if ($ujiCobaSampai === null) {
            return false;
        }

        $pada ??= CarbonImmutable::now();

        return $pada->startOfDay()->lessThanOrEqualTo(CarbonImmutable::parse($ujiCobaSampai)->startOfDay());
    }
}
