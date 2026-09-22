<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/** Daftar tertutup jenis partner (MARKETING.md 21); jenis di luar daftar ini ditolak, bukan disimpan apa adanya. */
enum JenisPartner: string
{
    case Consultant = 'Consultant';
    case SoftwareHouse = 'SoftwareHouse';
    case VendorMaintenance = 'VendorMaintenance';
    case VendorCalibration = 'VendorCalibration';
    case SystemIntegrator = 'SystemIntegrator';
    case ItConsultant = 'ItConsultant';
    case Reseller = 'Reseller';
    case Affiliate = 'Affiliate';

    public function label(): string
    {
        return match ($this) {
            self::Consultant => 'Konsultan',
            self::SoftwareHouse => 'Software House',
            self::VendorMaintenance => 'Vendor Maintenance',
            self::VendorCalibration => 'Vendor Kalibrasi',
            self::SystemIntegrator => 'System Integrator',
            self::ItConsultant => 'Konsultan TI',
            self::Reseller => 'Reseller',
            self::Affiliate => 'Affiliate',
        };
    }

    /** @return list<string> */
    public static function nilai(): array
    {
        return array_column(self::cases(), 'value');
    }
}
