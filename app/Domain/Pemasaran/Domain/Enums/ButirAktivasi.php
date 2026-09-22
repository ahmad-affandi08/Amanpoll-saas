<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

use App\Domain\Pemasaran\Domain\KatalogPeristiwaPemasaran;

/** Butir activation checklist trial (MARKETING.md 12). */
enum ButirAktivasi: string
{
    case OrganisasiDibuat = 'OrganisasiDibuat';
    case LokasiDibuat = 'LokasiDibuat';
    case AsetPertama = 'AsetPertama';
    case PenggunaDiundang = 'PenggunaDiundang';
    case PerintahKerjaPertama = 'PerintahKerjaPertama';
    case PreventifPertama = 'PreventifPertama';

    public function label(): string
    {
        return match ($this) {
            self::OrganisasiDibuat => 'Organisasi dibuat',
            self::LokasiDibuat => 'Lokasi dibuat',
            self::AsetPertama => 'Aset pertama ditambahkan',
            self::PenggunaDiundang => 'User/teknisi diundang',
            self::PerintahKerjaPertama => 'Work order pertama dibuat',
            self::PreventifPertama => 'Preventive pertama dibuat',
        };
    }

    /** Peristiwa pemasaran yang dicatat saat butir ini selesai; null bila tidak punya padanan. */
    public function peristiwa(): ?string
    {
        return match ($this) {
            self::OrganisasiDibuat => null,
            self::LokasiDibuat => KatalogPeristiwaPemasaran::LOKASI_PERTAMA_DIBUAT,
            self::AsetPertama => KatalogPeristiwaPemasaran::ASET_PERTAMA_DIBUAT,
            self::PenggunaDiundang => KatalogPeristiwaPemasaran::PENGGUNA_PERTAMA_DIUNDANG,
            self::PerintahKerjaPertama => KatalogPeristiwaPemasaran::PERINTAH_KERJA_PERTAMA_DIBUAT,
            self::PreventifPertama => KatalogPeristiwaPemasaran::PREVENTIVE_PERTAMA_DIBUAT,
        };
    }

    /**
     * Butir yang harus lengkap sebelum trial dianggap teraktivasi.
     *
     * @return list<self>
     */
    public static function wajibUntukAktivasi(): array
    {
        return [self::LokasiDibuat, self::AsetPertama, self::PerintahKerjaPertama];
    }
}
