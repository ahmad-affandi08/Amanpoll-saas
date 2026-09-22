<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/** Sepuluh jenis konten yang dikelola CMS (MARKETING.md 9). */
enum JenisKontenPemasaran: string
{
    case Artikel = 'Artikel';
    case Panduan = 'Panduan';
    case Glossary = 'Glossary';
    case CaseStudy = 'CaseStudy';
    case Template = 'Template';
    case Checklist = 'Checklist';
    case Ebook = 'Ebook';
    case FreeTool = 'FreeTool';
    case ComparisonPage = 'ComparisonPage';
    case IntegrationPage = 'IntegrationPage';

    /** Awalan jalur publiknya; jenis yang berbeda tinggal di rak yang berbeda. */
    public function awalanJalur(): string
    {
        return match ($this) {
            self::Artikel => '/artikel',
            self::Panduan => '/panduan',
            self::Glossary => '/glossary',
            self::CaseStudy => '/studi-kasus',
            self::Template => '/template',
            self::Checklist => '/checklist',
            self::Ebook => '/ebook',
            self::FreeTool => '/tools',
            self::ComparisonPage => '/perbandingan',
            self::IntegrationPage => '/integrasi',
        };
    }

    /** Jenis yang pantas dibagikan partner: bahan penjualan, bukan halaman rujukan atau alat interaktif. */
    public function materiPartner(): bool
    {
        return match ($this) {
            self::CaseStudy, self::Ebook, self::Panduan, self::Checklist, self::Template => true,
            self::Artikel, self::Glossary, self::FreeTool, self::ComparisonPage, self::IntegrationPage => false,
        };
    }

    /** @return list<string> */
    public static function nilaiMateriPartner(): array
    {
        return array_values(array_map(
            fn (self $satu): string => $satu->value,
            array_filter(self::cases(), fn (self $satu): bool => $satu->materiPartner()),
        ));
    }

    /** Pola ruas pertama rute konten; daftarnya tertutup supaya jalur lain tidak ikut tersapu. */
    public static function polaRak(): string
    {
        $awalan = array_map(
            fn (self $satu): string => preg_quote(trim($satu->awalanJalur(), '/'), '/'),
            self::cases(),
        );

        return implode('|', $awalan);
    }
}
