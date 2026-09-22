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
