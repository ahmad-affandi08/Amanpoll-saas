<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Ekspor;

/** Format berkas hasil ekspor laporan (21.05). */
enum FormatEkspor: string
{
    case Csv = 'Csv';
    case Xlsx = 'Xlsx';
    case Pdf = 'Pdf';

    public function ekstensi(): string
    {
        return match ($this) {
            self::Csv => 'csv',
            self::Xlsx => 'xlsx',
            self::Pdf => 'pdf',
        };
    }

    public function jenisMime(): string
    {
        return match ($this) {
            self::Csv => 'text/csv',
            self::Xlsx => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            self::Pdf => 'application/pdf',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Csv => 'CSV',
            self::Xlsx => 'Excel (XLSX)',
            self::Pdf => 'PDF',
        };
    }
}
