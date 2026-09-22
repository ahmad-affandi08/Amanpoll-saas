<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/**
 * Blok penyusun halaman pemasaran (MARKETING.md 8).
 *
 * Daftar tertutup, dan memang harus tertutup: setiap jenis punya komponen React
 * yang memetakan isinya. Jenis yang tidak dikenal akan tersimpan rapi di basis
 * data lalu hilang tanpa jejak saat halamannya dirender.
 */
enum JenisBlokHalaman: string
{
    case Hero = 'Hero';
    case TrustLogo = 'TrustLogo';
    case Masalah = 'Masalah';
    case Manfaat = 'Manfaat';
    case Fitur = 'Fitur';
    case Tangkapan = 'Tangkapan';
    case Video = 'Video';
    case Metrik = 'Metrik';
    case Testimoni = 'Testimoni';
    case StudiKasus = 'StudiKasus';
    case Perbandingan = 'Perbandingan';
    case Faq = 'Faq';
    case Harga = 'Harga';
    case Cta = 'Cta';
    case Formulir = 'Formulir';
    case ToolEmbed = 'ToolEmbed';
    case DaftarArtikel = 'DaftarArtikel';
    case Footer = 'Footer';

    /** Hanya blok ini yang boleh menunjuk satu FormulirPemasaran. */
    public function memakaiFormulir(): bool
    {
        return $this === self::Formulir;
    }
}
