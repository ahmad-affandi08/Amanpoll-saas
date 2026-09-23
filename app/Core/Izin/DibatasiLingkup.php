<?php

declare(strict_types=1);

namespace App\Core\Izin;

/**
 * Memasang batas unit/ruangan pada satu model.
 *
 * Dipasang bersama MilikOrganisasi, sesudahnya: tenancy lebih dulu, lingkup
 * menyusul. Model menyatakan sendiri kolom mana yang menentukan lingkupnya,
 * karena tidak semuanya punya kolom yang sama -- Lokasi misalnya dibatasi
 * lewat Id-nya sendiri, bukan lewat kolom asing.
 */
trait DibatasiLingkup
{
    protected static function bootDibatasiLingkup(): void
    {
        static::addGlobalScope(new ScopeLingkup);
    }

    /**
     * Peta kolom penentu lingkup: nama kolom => daftar pembanding.
     *
     * Baris cocok bila salah satu kolomnya masuk daftar yang diizinkan, bukan
     * keduanya: aset yang unitnya diizinkan tetap terlihat walau ruangannya
     * belum diisi.
     *
     * @return array<string, 'unit'|'lokasi'>
     */
    public function kolomLingkup(): array
    {
        return ['UnitOrganisasiId' => 'unit', 'LokasiId' => 'lokasi'];
    }
}
